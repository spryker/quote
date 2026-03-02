<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Quote\Dependency\Facade;

use Generated\Shared\Transfer\StoreTransfer;

interface QuoteToStoreFacadeInterface
{
    public function getCurrentStore(): StoreTransfer;

    public function getStoreByName(string $storeName): StoreTransfer;
}
