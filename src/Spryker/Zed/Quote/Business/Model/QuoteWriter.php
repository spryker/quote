<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Quote\Business\Model;

use Generated\Shared\Transfer\QuoteResponseTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Spryker\Zed\Kernel\Persistence\EntityManager\TransactionTrait;
use Spryker\Zed\Quote\Business\Quote\QuoteFieldsConfiguratorInterface;
use Spryker\Zed\Quote\Business\Validator\QuoteValidatorInterface;
use Spryker\Zed\Quote\Dependency\Facade\QuoteToStoreFacadeInterface;
use Spryker\Zed\Quote\Persistence\QuoteEntityManagerInterface;
use Spryker\Zed\Quote\Persistence\QuoteRepositoryInterface;

class QuoteWriter implements QuoteWriterInterface
{
    use TransactionTrait;

    /**
     * @var \Spryker\Zed\Quote\Persistence\QuoteEntityManagerInterface
     */
    protected $quoteEntityManager;

    /**
     * @var \Spryker\Zed\Quote\Dependency\Facade\QuoteToStoreFacadeInterface
     */
    protected $storeFacade;

    /**
     * @var \Spryker\Zed\Quote\Persistence\QuoteRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * @var \Spryker\Zed\Quote\Business\Model\QuoteWriterPluginExecutorInterface
     */
    protected $quoteWriterPluginExecutor;

    /**
     * @var \Spryker\Zed\Quote\Business\Validator\QuoteValidatorInterface
     */
    protected $quoteValidator;

    /**
     * @var array<\Spryker\Zed\QuoteExtension\Dependency\Plugin\QuoteExpandBeforeCreatePluginInterface>
     */
    protected $quoteExpandBeforeCreatePlugins;

    /**
     * @var \Spryker\Zed\Quote\Business\Quote\QuoteFieldsConfiguratorInterface
     */
    protected $quoteFieldsConfigurator;

    /**
     * @param \Spryker\Zed\Quote\Persistence\QuoteEntityManagerInterface $quoteEntityManager
     * @param \Spryker\Zed\Quote\Persistence\QuoteRepositoryInterface $quoteRepository
     * @param \Spryker\Zed\Quote\Business\Model\QuoteWriterPluginExecutorInterface $quoteWriterPluginExecutor
     * @param \Spryker\Zed\Quote\Dependency\Facade\QuoteToStoreFacadeInterface $storeFacade
     * @param \Spryker\Zed\Quote\Business\Validator\QuoteValidatorInterface $quoteValidator
     * @param \Spryker\Zed\Quote\Business\Quote\QuoteFieldsConfiguratorInterface $quoteFieldsConfigurator
     * @param array<\Spryker\Zed\QuoteExtension\Dependency\Plugin\QuoteExpandBeforeCreatePluginInterface> $quoteExpandBeforeCreatePlugins
     */
    public function __construct(
        QuoteEntityManagerInterface $quoteEntityManager,
        QuoteRepositoryInterface $quoteRepository,
        QuoteWriterPluginExecutorInterface $quoteWriterPluginExecutor,
        QuoteToStoreFacadeInterface $storeFacade,
        QuoteValidatorInterface $quoteValidator,
        QuoteFieldsConfiguratorInterface $quoteFieldsConfigurator,
        array $quoteExpandBeforeCreatePlugins = []
    ) {
        $this->quoteEntityManager = $quoteEntityManager;
        $this->storeFacade = $storeFacade;
        $this->quoteRepository = $quoteRepository;
        $this->quoteWriterPluginExecutor = $quoteWriterPluginExecutor;
        $this->quoteValidator = $quoteValidator;
        $this->quoteExpandBeforeCreatePlugins = $quoteExpandBeforeCreatePlugins;
        $this->quoteFieldsConfigurator = $quoteFieldsConfigurator;
    }

    public function save(QuoteTransfer $quoteTransfer): QuoteResponseTransfer
    {
        if ($quoteTransfer->getIdQuote()) {
            return $this->update($quoteTransfer);
        }

        return $this->create($quoteTransfer);
    }

    public function create(QuoteTransfer $quoteTransfer): QuoteResponseTransfer
    {
        if ($quoteTransfer->getIdQuote()) {
            return $this->createQuoteResponseTransfer($quoteTransfer);
        }

        $quoteTransfer = $this->addCurrentStoreToQuote($quoteTransfer);
        $quoteTransfer = $this->executeQuoteExpandBeforeCreatePlugins($quoteTransfer);

        $quoteValidationResponseTransfer = $this->quoteValidator->validate($quoteTransfer);

        if (!$quoteValidationResponseTransfer->getIsSuccessful()) {
            return $this->createQuoteResponseTransfer($quoteTransfer)
                ->setErrors($quoteValidationResponseTransfer->getErrors());
        }

        $quoteTransfer = $this->reloadStoreForQuote($quoteTransfer);

        return $this->getTransactionHandler()->handleTransaction(function () use ($quoteTransfer) {
            return $this->executeCreateTransaction($quoteTransfer);
        });
    }

    public function update(QuoteTransfer $quoteTransfer): QuoteResponseTransfer
    {
        $quoteByIdTransfer = $this->quoteRepository->findQuoteById($quoteTransfer->getIdQuote());
        if (!$quoteByIdTransfer) {
            return $this->createQuoteResponseTransfer($quoteTransfer);
        }

        $quoteValidationResponseTransfer = $this->quoteValidator->validate($quoteTransfer);

        if (!$quoteValidationResponseTransfer->getIsSuccessful()) {
            return $this->createQuoteResponseTransfer($quoteTransfer)
                ->setErrors($quoteValidationResponseTransfer->getErrors());
        }

        $quoteTransfer = $this->reloadStoreForQuote($quoteTransfer);

        return $this->getTransactionHandler()->handleTransaction(function () use ($quoteTransfer) {
            return $this->executeUpdateTransaction($quoteTransfer);
        });
    }

    protected function executeCreateTransaction(QuoteTransfer $quoteTransfer): QuoteResponseTransfer
    {
        $quoteTransfer = $this->quoteWriterPluginExecutor->executeCreateBeforePlugins($quoteTransfer);
        $quoteTransfer = $this->saveQuote($quoteTransfer);
        $quoteTransfer = $this->quoteWriterPluginExecutor->executeCreateAfterPlugins($quoteTransfer);

        return $this->createQuoteResponseTransfer($quoteTransfer)
            ->setIsSuccessful(true)
            ->setQuoteTransfer($quoteTransfer);
    }

    protected function executeUpdateTransaction(QuoteTransfer $quoteTransfer): QuoteResponseTransfer
    {
        $quoteTransfer = $this->quoteWriterPluginExecutor->executeUpdateBeforePlugins($quoteTransfer);
        $quoteTransfer = $this->saveQuote($quoteTransfer);
        $quoteTransfer = $this->quoteWriterPluginExecutor->executeUpdateAfterPlugins($quoteTransfer);

        return $this->createQuoteResponseTransfer($quoteTransfer)
            ->setIsSuccessful(true)
            ->setQuoteTransfer($quoteTransfer);
    }

    protected function addCurrentStoreToQuote(QuoteTransfer $quoteTransfer): QuoteTransfer
    {
        if (!$quoteTransfer->getStore()) {
            $quoteTransfer->setStore($this->storeFacade->getCurrentStore());
        }

        return $quoteTransfer;
    }

    protected function reloadStoreForQuote(QuoteTransfer $quoteTransfer): QuoteTransfer
    {
        if ($quoteTransfer->getStore()->getIdStore()) {
            return $quoteTransfer;
        }

        $store = $this->storeFacade->getStoreByName($quoteTransfer->getStore()->getName());
        $quoteTransfer->setStore($store);

        return $quoteTransfer;
    }

    protected function createQuoteResponseTransfer(QuoteTransfer $quoteTransfer): QuoteResponseTransfer
    {
        return (new QuoteResponseTransfer())
            ->setCustomer($quoteTransfer->getCustomer())
            ->setIsSuccessful(false);
    }

    protected function executeQuoteExpandBeforeCreatePlugins(QuoteTransfer $quoteTransfer): QuoteTransfer
    {
        foreach ($this->quoteExpandBeforeCreatePlugins as $quoteExpandBeforeCreatePlugin) {
            $quoteTransfer = $quoteExpandBeforeCreatePlugin->expand($quoteTransfer);
        }

        return $quoteTransfer;
    }

    protected function saveQuote(QuoteTransfer $quoteTransfer): QuoteTransfer
    {
        $quoteFieldsAllowedForSaving = $this->quoteFieldsConfigurator->getQuoteFieldsAllowedForSaving($quoteTransfer);

        return $this->quoteEntityManager->saveQuote($quoteTransfer, $quoteFieldsAllowedForSaving);
    }
}
