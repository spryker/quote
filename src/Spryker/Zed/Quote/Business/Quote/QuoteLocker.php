<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Quote\Business\Quote;

use Generated\Shared\Transfer\QuoteTransfer;

class QuoteLocker implements QuoteLockerInterface
{
    public function lock(QuoteTransfer $quoteTransfer): QuoteTransfer
    {
        return $quoteTransfer->setIsLocked(true);
    }

    public function unlock(QuoteTransfer $quoteTransfer): QuoteTransfer
    {
        return $quoteTransfer->setIsLocked(false);
    }
}
