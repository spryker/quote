<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Quote\Business\Quote;

use Generated\Shared\Transfer\QuoteTransfer;
use Spryker\Zed\Quote\QuoteConfig;

class QuoteFieldsConfigurator implements QuoteFieldsConfiguratorInterface
{
    /**
     * @var array<\Spryker\Zed\QuoteExtension\Dependency\Plugin\QuoteFieldsAllowedForSavingProviderPluginInterface>
     */
    protected $quoteFieldsAllowedForSavingProviderPlugins;

    /**
     * @var \Spryker\Zed\Quote\QuoteConfig
     */
    protected $quoteConfig;

    /**
     * @param \Spryker\Zed\Quote\QuoteConfig $quoteConfig
     * @param array<\Spryker\Zed\QuoteExtension\Dependency\Plugin\QuoteFieldsAllowedForSavingProviderPluginInterface> $quoteFieldsAllowedForSavingProviderPlugins
     */
    public function __construct(
        QuoteConfig $quoteConfig,
        array $quoteFieldsAllowedForSavingProviderPlugins = []
    ) {
        $this->quoteConfig = $quoteConfig;
        $this->quoteFieldsAllowedForSavingProviderPlugins = $quoteFieldsAllowedForSavingProviderPlugins;
    }

    /**
     * @param \Generated\Shared\Transfer\QuoteTransfer $quoteTransfer
     *
     * @return array<string|array<string>>
     */
    public function getQuoteFieldsAllowedForSaving(QuoteTransfer $quoteTransfer): array
    {
        $quoteFields = array_merge($this->quoteConfig->getQuoteFieldsAllowedForSaving(), $this->executeQuoteFieldsAllowedForSavingProviderPlugins($quoteTransfer));
        $quoteFields = array_unique($quoteFields);

        $quoteItemFields = $this->quoteConfig->getQuoteItemFieldsAllowedForSaving();

        if (!$quoteItemFields) {
            return $quoteFields;
        }

        $itemFieldsPosition = array_search(QuoteTransfer::ITEMS, $quoteFields, true);

        if ($itemFieldsPosition !== false) {
            $quoteFields[QuoteTransfer::ITEMS] = $quoteItemFields;
            unset($quoteFields[$itemFieldsPosition]);
        }

        return $quoteFields;
    }

    /**
     * @param \Generated\Shared\Transfer\QuoteTransfer $quoteTransfer
     *
     * @return array<string>
     */
    protected function executeQuoteFieldsAllowedForSavingProviderPlugins(QuoteTransfer $quoteTransfer): array
    {
        $quoteFieldsAllowedForSaving = [];
        foreach ($this->quoteFieldsAllowedForSavingProviderPlugins as $quoteFieldsAllowedForSavingProviderPlugin) {
            $quoteFieldsAllowedForSaving[] = $quoteFieldsAllowedForSavingProviderPlugin->execute($quoteTransfer);
        }

        return $quoteFieldsAllowedForSaving ? array_merge(...$quoteFieldsAllowedForSaving) : [];
    }
}
