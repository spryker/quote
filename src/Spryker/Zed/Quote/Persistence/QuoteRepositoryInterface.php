<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Quote\Persistence;

use DateTime;
use Generated\Shared\Transfer\QuoteCollectionTransfer;
use Generated\Shared\Transfer\QuoteCriteriaFilterTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Generated\Shared\Transfer\SpyQuoteEntityTransfer;

interface QuoteRepositoryInterface
{
    /**
     * Specification:
     * - Find quote by customer reference.
     *
     * @deprecated Use {@link findQuoteByCustomerReferenceAndIdStore()} instead.
     *
     * @param string $customerReference
     *
     * @return \Generated\Shared\Transfer\QuoteTransfer|null
     */
    public function findQuoteByCustomer($customerReference): ?QuoteTransfer;

    /**
     * Specification:
     * - Find quote by customer reference and ID store.
     *
     * @param string $customerReference
     * @param int $idStore
     *
     * @return \Generated\Shared\Transfer\QuoteTransfer|null
     */
    public function findQuoteByCustomerReferenceAndIdStore(string $customerReference, int $idStore): ?QuoteTransfer;

    /**
     * Specification:
     * - Find quote by quote id.
     *
     * @param int $idQuote
     *
     * @return \Generated\Shared\Transfer\QuoteTransfer|null
     */
    public function findQuoteById($idQuote): ?QuoteTransfer;

    /**
     * Specification:
     * - Get quote collection filtered by criteria
     *
     * @param \Generated\Shared\Transfer\QuoteCriteriaFilterTransfer $quoteCriteriaFilterTransfer
     *
     * @return \Generated\Shared\Transfer\QuoteCollectionTransfer
     */
    public function filterQuoteCollection(QuoteCriteriaFilterTransfer $quoteCriteriaFilterTransfer): QuoteCollectionTransfer;

    public function mapQuoteTransfer(SpyQuoteEntityTransfer $quoteEntityTransfer): QuoteTransfer;

    public function findExpiredGuestQuotes(DateTime $lifetimeLimitDate, int $limit): QuoteCollectionTransfer;

    public function findQuoteByUuid(string $uuidQuote): ?QuoteTransfer;

    public function acquireExclusiveQuoteLock(int $idQuote): bool;
}
