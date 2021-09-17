<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Quote\Business\Model;

use Generated\Shared\Transfer\CustomerTransfer;
use Generated\Shared\Transfer\QuoteResponseTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Spryker\Zed\Kernel\PermissionAwareTrait;
use Spryker\Zed\Kernel\Persistence\EntityManager\TransactionTrait;
use Spryker\Zed\Quote\Persistence\QuoteEntityManagerInterface;
use Spryker\Zed\Quote\Persistence\QuoteRepositoryInterface;

class QuoteDeleter implements QuoteDeleterInterface
{
    use PermissionAwareTrait;
    use TransactionTrait;

    /**
     * @var \Spryker\Zed\Quote\Persistence\QuoteEntityManagerInterface
     */
    protected $quoteEntityManager;

    /**
     * @var \Spryker\Zed\Quote\Persistence\QuoteRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * @var array<\Spryker\Zed\QuoteExtension\Dependency\Plugin\QuoteWritePluginInterface>
     */
    protected $quoteDeleteBeforePlugins;

    /**
     * @var array<\Spryker\Zed\QuoteExtension\Dependency\Plugin\QuoteDeleteAfterPluginInterface>
     */
    protected $quoteDeleteAfterPlugins;

    /**
     * @param \Spryker\Zed\Quote\Persistence\QuoteRepositoryInterface $quoteRepository
     * @param \Spryker\Zed\Quote\Persistence\QuoteEntityManagerInterface $quoteEntityManager
     * @param array<\Spryker\Zed\QuoteExtension\Dependency\Plugin\QuoteWritePluginInterface> $quoteDeleteBeforePlugins
     * @param array<\Spryker\Zed\QuoteExtension\Dependency\Plugin\QuoteDeleteAfterPluginInterface> $quoteDeleteAfterPlugins
     */
    public function __construct(
        QuoteRepositoryInterface $quoteRepository,
        QuoteEntityManagerInterface $quoteEntityManager,
        array $quoteDeleteBeforePlugins,
        array $quoteDeleteAfterPlugins
    ) {
        $this->quoteEntityManager = $quoteEntityManager;
        $this->quoteRepository = $quoteRepository;
        $this->quoteDeleteBeforePlugins = $quoteDeleteBeforePlugins;
        $this->quoteDeleteAfterPlugins = $quoteDeleteAfterPlugins;
    }

    /**
     * @param \Generated\Shared\Transfer\QuoteTransfer $quoteTransfer
     *
     * @return \Generated\Shared\Transfer\QuoteResponseTransfer
     */
    public function delete(QuoteTransfer $quoteTransfer): QuoteResponseTransfer
    {
        $quoteResponseTransfer = new QuoteResponseTransfer();
        $quoteResponseTransfer->setIsSuccessful(false);
        if ($this->validateQuote($quoteTransfer)) {
            return $this->getTransactionHandler()->handleTransaction(function () use ($quoteTransfer) {
                return $this->executeDeleteTransaction($quoteTransfer);
            });
        }

        return $quoteResponseTransfer;
    }

    /**
     * @param \Generated\Shared\Transfer\QuoteTransfer $quoteTransfer
     *
     * @return bool
     */
    protected function validateQuote(QuoteTransfer $quoteTransfer): bool
    {
        if (!$quoteTransfer->getCustomer()) {
            return false;
        }
        $loadedQuoteTransfer = $this->quoteRepository->findQuoteById($quoteTransfer->getIdQuote());
        if (!$loadedQuoteTransfer) {
            return false;
        }
        $customerTransfer = $quoteTransfer->getCustomer();

        return $this->isDeleteAllowed($loadedQuoteTransfer, $customerTransfer);
    }

    /**
     * @param \Generated\Shared\Transfer\QuoteTransfer $quoteTransfer
     *
     * @return \Generated\Shared\Transfer\QuoteResponseTransfer
     */
    protected function executeDeleteTransaction(QuoteTransfer $quoteTransfer): QuoteResponseTransfer
    {
        $quoteResponseTransfer = new QuoteResponseTransfer();
        $quoteTransfer = $this->executeDeleteBeforePlugins($quoteTransfer);
        $this->quoteEntityManager->deleteQuoteById($quoteTransfer->getIdQuote());
        $this->executeDeleteAfterPlugins($quoteTransfer);
        $quoteResponseTransfer->setCustomer($quoteTransfer->getCustomer());
        $quoteResponseTransfer->setIsSuccessful(true);

        return $quoteResponseTransfer;
    }

    /**
     * @param \Generated\Shared\Transfer\QuoteTransfer $quoteTransfer
     *
     * @return \Generated\Shared\Transfer\QuoteTransfer
     */
    protected function executeDeleteBeforePlugins(QuoteTransfer $quoteTransfer): QuoteTransfer
    {
        foreach ($this->quoteDeleteBeforePlugins as $quoteWritePlugin) {
            $quoteTransfer = $quoteWritePlugin->execute($quoteTransfer);
        }

        return $quoteTransfer;
    }

    /**
     * @param \Generated\Shared\Transfer\QuoteTransfer $quoteTransfer
     *
     * @return \Generated\Shared\Transfer\QuoteTransfer
     */
    protected function executeDeleteAfterPlugins(QuoteTransfer $quoteTransfer): QuoteTransfer
    {
        foreach ($this->quoteDeleteAfterPlugins as $quoteDeleteAfterPlugin) {
            $quoteTransfer = $quoteDeleteAfterPlugin->execute($quoteTransfer);
        }

        return $quoteTransfer;
    }

    /**
     * @param \Generated\Shared\Transfer\QuoteTransfer $quoteTransfer
     * @param \Generated\Shared\Transfer\CustomerTransfer $customerTransfer
     *
     * @return bool
     */
    protected function isDeleteAllowed(QuoteTransfer $quoteTransfer, CustomerTransfer $customerTransfer): bool
    {
        return $quoteTransfer->getCustomerReference() === $customerTransfer->getCustomerReference()
            || ($customerTransfer->getCompanyUserTransfer()
                && $this->can('WriteSharedCartPermissionPlugin', $customerTransfer->getCompanyUserTransfer()->getIdCompanyUser(), $quoteTransfer->getIdQuote())
            );
    }
}
