<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\Quote\Dependency\Client;

use Generated\Shared\Transfer\CustomerTransfer;

class QuoteToCustomerClientBridge implements QuoteToCustomerClientInterface
{
    /**
     * @var \Spryker\Client\Customer\CustomerClientInterface
     */
    protected $customerClient;

    /**
     * @param \Spryker\Client\Customer\CustomerClientInterface $customerClient
     */
    public function __construct($customerClient)
    {
        $this->customerClient = $customerClient;
    }

    public function isLoggedIn(): bool
    {
        return $this->customerClient->isLoggedIn();
    }

    public function getCustomer(): ?CustomerTransfer
    {
        return $this->customerClient->getCustomer();
    }
}
