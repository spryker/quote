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
use Orm\Zed\Customer\Persistence\Map\SpyCustomerTableMap;
use Orm\Zed\Quote\Persistence\Map\SpyQuoteTableMap;
use Orm\Zed\Quote\Persistence\SpyQuoteQuery;
use PDOException;
use Spryker\Zed\Kernel\Persistence\AbstractRepository;
use Spryker\Zed\PropelOrm\Business\Runtime\ActiveQuery\Criteria;
use Throwable;

/**
 * @method \Spryker\Zed\Quote\Persistence\QuotePersistenceFactory getFactory()
 */
class QuoteRepository extends AbstractRepository implements QuoteRepositoryInterface
{
    /**
     * PostgreSQL `lock_not_available`: the row is held by another transaction and `NOWAIT` refused
     * to block.
     */
    protected const string SQL_STATE_LOCK_NOT_AVAILABLE = '55P03';

    /**
     * MySQL and MariaDB report the same condition as `ER_LOCK_NOWAIT` under the catch-all `HY000`
     * SQL state, so only the driver code identifies it.
     */
    protected const int DRIVER_CODE_LOCK_NOT_AVAILABLE = 3572;

    protected const int ERROR_INFORMATION_INDEX_SQL_STATE = 0;

    protected const int ERROR_INFORMATION_INDEX_DRIVER_CODE = 1;

    /**
     * {@inheritDoc}
     *
     * @deprecated Use {@link findQuoteByCustomerReferenceAndIdStore()} instead.
     *
     * @param string $customerReference
     *
     * @return \Generated\Shared\Transfer\QuoteTransfer|null
     */
    public function findQuoteByCustomer($customerReference): ?QuoteTransfer
    {
        $quoteQuery = $this->getFactory()->createQuoteQuery()
            ->joinWithSpyStore()
            ->filterByCustomerReference($customerReference);

        $quoteEntityTransfer = $this->buildQueryFromCriteria($quoteQuery)->findOne();
        if (!$quoteEntityTransfer) {
            return null;
        }

        return $this->getFactory()->createQuoteMapper()->mapQuoteTransfer($quoteEntityTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @param string $customerReference
     * @param int $idStore
     *
     * @return \Generated\Shared\Transfer\QuoteTransfer|null
     */
    public function findQuoteByCustomerReferenceAndIdStore(string $customerReference, int $idStore): ?QuoteTransfer
    {
        $quoteQuery = $this->getFactory()->createQuoteQuery()
            ->joinWithSpyStore()
            ->filterByCustomerReference($customerReference)
            ->filterByFkStore($idStore);

        $quoteEntity = $this->buildQueryFromCriteria($quoteQuery)->findOne();
        if (!$quoteEntity) {
            return null;
        }

        return $this->getFactory()->createQuoteMapper()->mapQuoteTransfer($quoteEntity);
    }

    /**
     * {@inheritDoc}
     *
     * @param int $idQuote
     *
     * @return \Generated\Shared\Transfer\QuoteTransfer|null
     */
    public function findQuoteById($idQuote): ?QuoteTransfer
    {
        $quoteQuery = $this->getFactory()->createQuoteQuery()
            ->joinWithSpyStore()
            ->filterByIdQuote($idQuote);

        $quoteEntityTransfer = $this->buildQueryFromCriteria($quoteQuery)->findOne();
        if (!$quoteEntityTransfer) {
            return null;
        }

        return $this->getFactory()->createQuoteMapper()->mapQuoteTransfer($quoteEntityTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @param \Generated\Shared\Transfer\QuoteCriteriaFilterTransfer $quoteCriteriaFilterTransfer
     *
     * @return \Generated\Shared\Transfer\QuoteCollectionTransfer
     */
    public function filterQuoteCollection(QuoteCriteriaFilterTransfer $quoteCriteriaFilterTransfer): QuoteCollectionTransfer
    {
        $quoteQuery = $this->getFactory()
            ->createQuoteQuery()
            ->joinWithSpyStore()
            ->orderByIdQuote();

        $quoteQuery = $this->applyQuoteCriteriaFilters($quoteQuery, $quoteCriteriaFilterTransfer);
        $quoteEntityCollectionTransfer = $this->buildQueryFromCriteria($quoteQuery, $quoteCriteriaFilterTransfer->getFilter())->find();

        $quoteCollectionTransfer = new QuoteCollectionTransfer();
        $quoteMapper = $this->getFactory()->createQuoteMapper();
        foreach ($quoteEntityCollectionTransfer as $quoteEntityTransfer) {
            $quoteCollectionTransfer->addQuote($quoteMapper->mapQuoteTransfer($quoteEntityTransfer));
        }

        return $quoteCollectionTransfer;
    }

    protected function applyQuoteCriteriaFilters(SpyQuoteQuery $quoteQuery, QuoteCriteriaFilterTransfer $quoteCriteriaFilterTransfer): SpyQuoteQuery
    {
        if ($quoteCriteriaFilterTransfer->getCustomerReference()) {
            $quoteQuery->filterByCustomerReference($quoteCriteriaFilterTransfer->getCustomerReference());
        }

        if ($quoteCriteriaFilterTransfer->isPropertyModified(QuoteCriteriaFilterTransfer::QUOTE_IDS)) {
            $quoteQuery->filterByIdQuote_In($quoteCriteriaFilterTransfer->getQuoteIds());
        }

        if ($quoteCriteriaFilterTransfer->getIdStore()) {
            $quoteQuery->filterByFkStore($quoteCriteriaFilterTransfer->getIdStore());
        }

        return $quoteQuery;
    }

    public function mapQuoteTransfer(SpyQuoteEntityTransfer $quoteEntityTransfer): QuoteTransfer
    {
        return $this->getFactory()->createQuoteMapper()->mapQuoteTransfer($quoteEntityTransfer);
    }

    public function findExpiredGuestQuotes(DateTime $lifetimeLimitDate, int $limit): QuoteCollectionTransfer
    {
        $quoteQuery = $this->getFactory()
            ->createQuoteQuery()
            ->joinWithSpyStore()
            ->addJoin(SpyQuoteTableMap::COL_CUSTOMER_REFERENCE, SpyCustomerTableMap::COL_CUSTOMER_REFERENCE, Criteria::LEFT_JOIN)
            ->filterByUpdatedAt(['max' => $lifetimeLimitDate], Criteria::LESS_EQUAL)
            ->where(SpyCustomerTableMap::COL_CUSTOMER_REFERENCE . Criteria::ISNULL)
            ->orderByUpdatedAt()
            ->limit($limit);

        $quoteEntityCollectionTransfer = $this->buildQueryFromCriteria($quoteQuery)->find();

        $quoteMapper = $this->getFactory()->createQuoteMapper();
        $quoteCollectionTransfer = new QuoteCollectionTransfer();
        foreach ($quoteEntityCollectionTransfer as $quoteEntityTransfer) {
            $quoteCollectionTransfer->addQuote($quoteMapper->mapQuoteTransfer($quoteEntityTransfer));
        }

        return $quoteCollectionTransfer;
    }

    public function findQuoteByUuid(string $uuidQuote): ?QuoteTransfer
    {
        $quoteQuery = $this->getFactory()
            ->createQuoteQuery()
            ->joinWithSpyStore()
            ->filterByUuid($uuidQuote);

        $quoteEntityTransfer = $this->buildQueryFromCriteria($quoteQuery)->findOne();
        if (!$quoteEntityTransfer) {
            return null;
        }

        return $this->getFactory()->createQuoteMapper()->mapQuoteTransfer($quoteEntityTransfer);
    }

    /**
     * Acquires an exclusive database-level lock on a quote record.
     *
     * Selects the row under an exclusive, non-blocking lock, so that only one process at a time can
     * go on to modify the quote and a process that loses the race is told immediately instead of
     * waiting. Must be called inside an active database transaction to be effective.
     *
     * The locking clause is emitted by the Propel adapter, so each platform gets the syntax it
     * supports. Returns false only when the lock could not be taken — the row is gone, or another
     * transaction holds it; anything else the database reports is rethrown.
     *
     * @param int $idQuote
     *
     * @throws \Throwable
     *
     * @return bool
     */
    public function acquireExclusiveQuoteLock(int $idQuote): bool
    {
        try {
            // select() rather than a hydrated find: the row is only being probed, and hydrating it
            // would seed Propel's instance pool, so a later findPk() in the same request would be
            // served the locked snapshot instead of reading through.
            return $this->getFactory()
                ->createQuoteQuery()
                ->filterByIdQuote($idQuote)
                ->lockForUpdate([], true)
                ->select([SpyQuoteTableMap::COL_ID_QUOTE])
                ->findOne() !== null;
        } catch (Throwable $exception) {
            if ($this->isLockUnavailable($exception)) {
                return false;
            }

            throw $exception;
        }
    }

    /**
     * Propel wraps the driver exception in a `QueryExecutionException`, so the chain has to be
     * walked down to the `PDOException` that carries the platform's error identifiers.
     */
    protected function isLockUnavailable(Throwable $exception): bool
    {
        for ($candidate = $exception; $candidate !== null; $candidate = $candidate->getPrevious()) {
            if (!$candidate instanceof PDOException) {
                continue;
            }

            $errorInformation = $candidate->errorInfo ?? [];

            if (($errorInformation[static::ERROR_INFORMATION_INDEX_SQL_STATE] ?? null) === static::SQL_STATE_LOCK_NOT_AVAILABLE) {
                return true;
            }

            if ((int)($errorInformation[static::ERROR_INFORMATION_INDEX_DRIVER_CODE] ?? 0) === static::DRIVER_CODE_LOCK_NOT_AVAILABLE) {
                return true;
            }
        }

        return false;
    }
}
