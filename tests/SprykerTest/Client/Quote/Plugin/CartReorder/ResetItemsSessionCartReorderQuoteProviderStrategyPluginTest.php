<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerTest\Client\Quote\Plugin\CartReorder;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\CartReorderRequestTransfer;
use Spryker\Client\Quote\Plugin\CartReorder\ResetItemsSessionCartReorderQuoteProviderStrategyPlugin;
use Spryker\Client\Quote\QuoteClient;
use Spryker\Client\Quote\QuoteFactory;
use Spryker\Client\Session\SessionClientInterface;
use Spryker\Shared\Quote\QuoteConfig;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Client
 * @group Quote
 * @group Plugin
 * @group CartReorder
 * @group ResetItemsSessionCartReorderQuoteProviderStrategyPluginTest
 * Add your own group annotations below this line
 */
class ResetItemsSessionCartReorderQuoteProviderStrategyPluginTest extends Unit
{
    public function testGivenTheSessionStrategyAndAStartedSessionWhenCheckingApplicabilityThenThePluginApplies(): void
    {
        // Arrange
        $plugin = $this->createPlugin(QuoteConfig::STORAGE_STRATEGY_SESSION, true);

        // Act
        $isApplicable = $plugin->isApplicable(new CartReorderRequestTransfer());

        // Assert
        $this->assertTrue($isApplicable);
    }

    public function testGivenTheSessionStrategyAndNoStartedSessionWhenCheckingApplicabilityThenThePluginDoesNotApply(): void
    {
        // Arrange
        $plugin = $this->createPlugin(QuoteConfig::STORAGE_STRATEGY_SESSION, false);

        // Act
        $isApplicable = $plugin->isApplicable(new CartReorderRequestTransfer());

        // Assert
        $this->assertFalse($isApplicable);
    }

    public function testGivenTheDatabaseStrategyAndAStartedSessionWhenCheckingApplicabilityThenThePluginDoesNotApply(): void
    {
        // Arrange
        $plugin = $this->createPlugin(QuoteConfig::STORAGE_STRATEGY_DATABASE, true);

        // Act
        $isApplicable = $plugin->isApplicable(new CartReorderRequestTransfer());

        // Assert
        $this->assertFalse($isApplicable);
    }

    protected function createPlugin(string $storageStrategy, bool $isSessionStarted): ResetItemsSessionCartReorderQuoteProviderStrategyPlugin
    {
        $quoteClientMock = $this->createMock(QuoteClient::class);
        $quoteClientMock->method('getStorageStrategy')->willReturn($storageStrategy);

        $sessionClientMock = $this->createMock(SessionClientInterface::class);
        $sessionClientMock->method('isStarted')->willReturn($isSessionStarted);

        $quoteFactoryMock = $this->createMock(QuoteFactory::class);
        $quoteFactoryMock->method('getSessionClient')->willReturn($sessionClientMock);

        $plugin = new ResetItemsSessionCartReorderQuoteProviderStrategyPlugin();
        $plugin->setClient($quoteClientMock);
        $plugin->setFactory($quoteFactoryMock);

        return $plugin;
    }
}
