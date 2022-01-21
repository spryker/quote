<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Quote\Business\Quote;

use Generated\Shared\Transfer\QuoteTransfer;

interface QuoteFieldsConfiguratorInterface
{
    /**
     * @param \Generated\Shared\Transfer\QuoteTransfer $quoteTransfer
     *
     * @return array<string|array<string>>
     */
    public function getQuoteFieldsAllowedForSaving(QuoteTransfer $quoteTransfer): array;
}
