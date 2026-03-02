<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\Quote\Zed;

use Generated\Shared\Transfer\QuoteResponseTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Spryker\Client\ZedRequest\ZedRequestClientInterface;

class QuoteStub implements QuoteStubInterface
{
    /**
     * @var \Spryker\Client\ZedRequest\ZedRequestClientInterface
     */
    protected $zedStub;

    public function __construct(ZedRequestClientInterface $zedStub)
    {
        $this->zedStub = $zedStub;
    }

    public function deleteQuote(QuoteTransfer $quoteTransfer): QuoteResponseTransfer
    {
        /** @var \Generated\Shared\Transfer\QuoteResponseTransfer $quoteResponseTransfer */
        $quoteResponseTransfer = $this->zedStub->call('/quote/gateway/delete-quote', $quoteTransfer);

        return $quoteResponseTransfer;
    }
}
