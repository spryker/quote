<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Shared\Quote;

use Spryker\Shared\Kernel\AbstractSharedConfig;

class QuoteConfig extends AbstractSharedConfig
{
    /**
     * @var string
     */
    public const STORAGE_STRATEGY_SESSION = 'session';

    /**
     * @var string
     */
    public const STORAGE_STRATEGY_DATABASE = 'database';

    /**
     * @api
     *
     * @return string
     */
    public function getStorageStrategy()
    {
        return static::STORAGE_STRATEGY_SESSION;
    }
}
