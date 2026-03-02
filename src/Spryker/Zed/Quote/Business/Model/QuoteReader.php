<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Quote\Business\Model;

use Generated\Shared\Transfer\CustomerTransfer;
use Generated\Shared\Transfer\QuoteCollectionTransfer;
use Generated\Shared\Transfer\QuoteCriteriaFilterTransfer;
use Generated\Shared\Transfer\QuoteResponseTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Generated\Shared\Transfer\StoreTransfer;
use Spryker\Zed\Quote\Dependency\Facade\QuoteToStoreFacadeInterface;
use Spryker\Zed\Quote\Persistence\QuoteRepositoryInterface;
use Spryker\Zed\QuoteExtension\Dependency\Plugin\QuotePostExpanderPluginInterface;
use Spryker\Zed\QuoteExtension\Dependency\Plugin\QuotePreExpanderPluginInterface;

class QuoteReader implements QuoteReaderInterface
{
    /**
     * @var \Spryker\Zed\Quote\Dependency\Facade\QuoteToStoreFacadeInterface
     */
    protected $storeFacade;

    /**
     * @var \Spryker\Zed\Quote\Persistence\QuoteRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * @var array<\Spryker\Zed\QuoteExtension\Dependency\Plugin\QuoteExpanderPluginInterface>
     */
    protected $quoteExpanderPlugins;

    /**
     * @var list<\Spryker\Zed\QuoteExtension\Dependency\Plugin\QuoteCollectionFilterPluginInterface>
     */
    protected array $quoteCollectionFilterPlugins;

    /**
     * @param \Spryker\Zed\Quote\Persistence\QuoteRepositoryInterface $quoteRepository
     * @param array<\Spryker\Zed\QuoteExtension\Dependency\Plugin\QuoteExpanderPluginInterface> $quoteExpanderPlugins
     * @param \Spryker\Zed\Quote\Dependency\Facade\QuoteToStoreFacadeInterface $storeFacade
     * @param list<\Spryker\Zed\QuoteExtension\Dependency\Plugin\QuoteCollectionFilterPluginInterface> $quoteCollectionFilterPlugins
     */
    public function __construct(
        QuoteRepositoryInterface $quoteRepository,
        array $quoteExpanderPlugins,
        QuoteToStoreFacadeInterface $storeFacade,
        array $quoteCollectionFilterPlugins
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->quoteExpanderPlugins = $quoteExpanderPlugins;
        $this->storeFacade = $storeFacade;
        $this->quoteCollectionFilterPlugins = $quoteCollectionFilterPlugins;
    }

    /**
     * @deprecated Use {@link findQuoteByCustomerAndStore()} instead.
     *
     * @param \Generated\Shared\Transfer\CustomerTransfer $customerTransfer
     *
     * @return \Generated\Shared\Transfer\QuoteResponseTransfer
     */
    public function findQuoteByCustomer(CustomerTransfer $customerTransfer): QuoteResponseTransfer
    {
        $customerTransfer->requireCustomerReference();
        $quoteResponseTransfer = new QuoteResponseTransfer();
        $quoteResponseTransfer->setIsSuccessful(false);
        $quoteTransfer = $this->quoteRepository->findQuoteByCustomerReferenceAndIdStore(
            $customerTransfer->getCustomerReference(),
            $this->storeFacade->getCurrentStore()->getIdStore(),
        );

        if ($quoteTransfer) {
            $quoteTransfer = $this->executeExpandQuotePlugins($quoteTransfer);
            $this->executePostExpandQuotePlugins();
            $quoteResponseTransfer = $this->setQuoteResponseTransfer($quoteResponseTransfer, $quoteTransfer);
            $quoteResponseTransfer->setCustomer($customerTransfer);
        }

        return $quoteResponseTransfer;
    }

    public function findQuoteByCustomerAndStore(CustomerTransfer $customerTransfer, StoreTransfer $storeTransfer): QuoteResponseTransfer
    {
        $customerTransfer->requireCustomerReference();
        $storeTransfer->requireIdStore();
        $quoteResponseTransfer = new QuoteResponseTransfer();
        $quoteResponseTransfer->setIsSuccessful(false);
        $quoteTransfer = $this->quoteRepository
            ->findQuoteByCustomerReferenceAndIdStore(
                $customerTransfer->getCustomerReference(),
                $storeTransfer->getIdStore(),
            );

        if ($quoteTransfer) {
            $quoteTransfer = $this->executeExpandQuotePlugins($quoteTransfer);
            $this->executePostExpandQuotePlugins();
            $quoteResponseTransfer = $this->setQuoteResponseTransfer($quoteResponseTransfer, $quoteTransfer);
            $quoteResponseTransfer->setCustomer($customerTransfer);
        }

        return $quoteResponseTransfer;
    }

    /**
     * @param int $idQuote
     *
     * @return \Generated\Shared\Transfer\QuoteResponseTransfer
     */
    public function findQuoteById($idQuote): QuoteResponseTransfer
    {
        $quoteResponseTransfer = new QuoteResponseTransfer();
        $quoteResponseTransfer->setIsSuccessful(false);
        $quoteTransfer = $this->quoteRepository
            ->findQuoteById($idQuote);

        if ($quoteTransfer) {
            $quoteTransfer = $this->executeExpandQuotePlugins($quoteTransfer);
            $this->executePostExpandQuotePlugins();
        }

        return $this->setQuoteResponseTransfer($quoteResponseTransfer, $quoteTransfer);
    }

    protected function setQuoteResponseTransfer(QuoteResponseTransfer $quoteResponseTransfer, ?QuoteTransfer $quoteTransfer = null): QuoteResponseTransfer
    {
        if (!$quoteTransfer) {
            $quoteResponseTransfer->setIsSuccessful(false);

            return $quoteResponseTransfer;
        }

        $quoteResponseTransfer->setQuoteTransfer($quoteTransfer);
        $quoteResponseTransfer->setIsSuccessful(true);

        return $quoteResponseTransfer;
    }

    public function findQuoteByUuid(QuoteTransfer $quoteTransfer): QuoteResponseTransfer
    {
        $quoteTransfer->requireUuid();
        $quoteResponseTransfer = (new QuoteResponseTransfer())
            ->setIsSuccessful(false);
        $quoteTransfer = $this->quoteRepository
            ->findQuoteByUuid($quoteTransfer->getUuid());

        if (!$quoteTransfer) {
            return $quoteResponseTransfer;
        }

        $quoteTransfer = $this->executeExpandQuotePlugins($quoteTransfer);
        $this->executePostExpandQuotePlugins();

        return $quoteResponseTransfer
            ->setQuoteTransfer($quoteTransfer)
            ->setIsSuccessful(true);
    }

    public function getFilteredQuoteCollection(QuoteCriteriaFilterTransfer $quoteCriteriaFilterTransfer): QuoteCollectionTransfer
    {
        $quoteCollectionTransfer = $this->quoteRepository->filterQuoteCollection($quoteCriteriaFilterTransfer);

        $quoteCollectionTransfer = $this->executeQuoteCollectionFilterPlugins(
            $quoteCollectionTransfer,
            $quoteCriteriaFilterTransfer,
        );

        return $this->executeExpandQuotePluginsForQuoteCollection($quoteCollectionTransfer);
    }

    protected function executeExpandQuotePluginsForQuoteCollection(
        QuoteCollectionTransfer $quoteCollectionTransfer
    ): QuoteCollectionTransfer {
        $expandedQuotesCollection = new QuoteCollectionTransfer();

        foreach ($quoteCollectionTransfer->getQuotes() as $quoteTransfer) {
            $this->executePreExpandQuotePlugins($quoteTransfer);
        }

        foreach ($quoteCollectionTransfer->getQuotes() as $quoteTransfer) {
            $expandedQuotesCollection->addQuote(
                $this->executeExpandQuotePlugins($quoteTransfer),
            );
        }

        $this->executePostExpandQuotePlugins();

        return $expandedQuotesCollection;
    }

    protected function executePreExpandQuotePlugins(QuoteTransfer $quoteTransfer): void
    {
        foreach ($this->quoteExpanderPlugins as $quoteExpanderPlugin) {
            if ($quoteExpanderPlugin instanceof QuotePreExpanderPluginInterface) {
                $quoteExpanderPlugin->preExpand($quoteTransfer);
            }
        }
    }

    protected function executeExpandQuotePlugins(QuoteTransfer $quoteTransfer): QuoteTransfer
    {
        foreach ($this->quoteExpanderPlugins as $quoteExpanderPlugin) {
            $quoteTransfer = $quoteExpanderPlugin->expand($quoteTransfer);
        }

        return $quoteTransfer;
    }

    protected function executePostExpandQuotePlugins(): void
    {
        foreach ($this->quoteExpanderPlugins as $quoteExpanderPlugin) {
            if ($quoteExpanderPlugin instanceof QuotePostExpanderPluginInterface) {
                $quoteExpanderPlugin->postExpand();
            }
        }
    }

    protected function executeQuoteCollectionFilterPlugins(
        QuoteCollectionTransfer $quoteCollectionTransfer,
        QuoteCriteriaFilterTransfer $quoteCriteriaFilterTransfer
    ): QuoteCollectionTransfer {
        foreach ($this->quoteCollectionFilterPlugins as $quoteCollectionFilterPlugin) {
            $quoteCollectionTransfer = $quoteCollectionFilterPlugin->filter($quoteCollectionTransfer, $quoteCriteriaFilterTransfer);
        }

        return $quoteCollectionTransfer;
    }
}
