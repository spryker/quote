<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Quote;

use Spryker\Zed\Kernel\AbstractBundleDependencyProvider;
use Spryker\Zed\Kernel\Container;
use Spryker\Zed\Quote\Dependency\Facade\QuoteToStoreFacadeBridge;
use Spryker\Zed\Quote\Dependency\Service\QuoteToUtilEncodingServiceBridge;

/**
 * @method \Spryker\Zed\Quote\QuoteConfig getConfig()
 */
class QuoteDependencyProvider extends AbstractBundleDependencyProvider
{
    /**
     * @var string
     */
    public const FACADE_STORE = 'FACADE_STORE';

    /**
     * @var string
     */
    public const PLUGINS_QUOTE_CREATE_AFTER = 'PLUGINS_QUOTE_CREATE_AFTER';

    /**
     * @var string
     */
    public const PLUGINS_QUOTE_CREATE_BEFORE = 'PLUGINS_QUOTE_CREATE_BEFORE';

    /**
     * @var string
     */
    public const PLUGINS_QUOTE_UPDATE_AFTER = 'PLUGINS_QUOTE_UPDATE_AFTER';

    /**
     * @var string
     */
    public const PLUGINS_QUOTE_UPDATE_BEFORE = 'PLUGINS_QUOTE_UPDATE_BEFORE';

    /**
     * @var string
     */
    public const SERVICE_UTIL_ENCODING = 'SERVICE_UTIL_ENCODING';

    /**
     * @var string
     */
    public const PLUGINS_QUOTE_DELETE_BEFORE = 'PLUGINS_QUOTE_DELETE_BEFORE';

    /**
     * @var string
     */
    public const PLUGINS_QUOTE_DELETE_AFTER = 'PLUGINS_QUOTE_DELETE_AFTER';

    /**
     * @var string
     */
    public const PLUGINS_QUOTE_VALIDATOR = 'PLUGINS_QUOTE_VALIDATOR';

    /**
     * @var string
     */
    public const PLUGINS_QUOTE_EXPANDER = 'PLUGINS_QUOTE_EXPANDER';

    /**
     * @var string
     */
    public const PLUGINS_QUOTE_EXPAND_BEFORE_CREATE = 'PLUGINS_QUOTE_EXPAND_BEFORE_CREATE';

    /**
     * @var string
     */
    public const PLUGINS_QUOTE_FIELDS_ALLOWED_FOR_SAVING_PROVIDER = 'PLUGINS_QUOTE_FIELDS_ALLOWED_FOR_SAVING_PROVIDER';

    /**
     * @param \Spryker\Zed\Kernel\Container $container
     *
     * @return \Spryker\Zed\Kernel\Container
     */
    public function provideBusinessLayerDependencies(Container $container): Container
    {
        $container = parent::provideBusinessLayerDependencies($container);

        $container = $this->addStoreFacade($container);
        $container = $this->addQuoteCreateAfterPlugins($container);
        $container = $this->addQuoteCreateBeforePlugins($container);
        $container = $this->addQuoteUpdateAfterPlugins($container);
        $container = $this->addQuoteUpdateBeforePlugins($container);
        $container = $this->addQuoteDeleteBeforePlugins($container);
        $container = $this->addQuoteDeleteAfterPlugins($container);
        $container = $this->addQuoteValidatorPlugins($container);
        $container = $this->addQuoteExpanderPlugins($container);
        $container = $this->addQuoteExpandBeforeCreatePlugins($container);
        $container = $this->addQuoteFieldsAllowedForSavingProviderPlugins($container);

        return $container;
    }

    /**
     * @param \Spryker\Zed\Kernel\Container $container
     *
     * @return \Spryker\Zed\Kernel\Container
     */
    public function providePersistenceLayerDependencies(Container $container): Container
    {
        $container = $this->addUtilEncodingService($container);

        return $container;
    }

    /**
     * @param \Spryker\Zed\Kernel\Container $container
     *
     * @return \Spryker\Zed\Kernel\Container
     */
    protected function addUtilEncodingService(Container $container): Container
    {
        $container->set(static::SERVICE_UTIL_ENCODING, function (Container $container) {
            return new QuoteToUtilEncodingServiceBridge($container->getLocator()->utilEncoding()->service());
        });

        return $container;
    }

    /**
     * @param \Spryker\Zed\Kernel\Container $container
     *
     * @return \Spryker\Zed\Kernel\Container
     */
    protected function addStoreFacade(Container $container): Container
    {
        $container->set(static::FACADE_STORE, function (Container $container) {
            return new QuoteToStoreFacadeBridge($container->getLocator()->store()->facade());
        });

        return $container;
    }

    /**
     * @param \Spryker\Zed\Kernel\Container $container
     *
     * @return \Spryker\Zed\Kernel\Container
     */
    protected function addQuoteCreateAfterPlugins(Container $container): Container
    {
        $container->set(static::PLUGINS_QUOTE_CREATE_AFTER, function (Container $container) {
            return $this->getQuoteCreateAfterPlugins();
        });

        return $container;
    }

    /**
     * @param \Spryker\Zed\Kernel\Container $container
     *
     * @return \Spryker\Zed\Kernel\Container
     */
    protected function addQuoteExpanderPlugins(Container $container): Container
    {
        $container->set(static::PLUGINS_QUOTE_EXPANDER, function (Container $container) {
            return $this->getQuoteExpanderPlugins();
        });

        return $container;
    }

    /**
     * @param \Spryker\Zed\Kernel\Container $container
     *
     * @return \Spryker\Zed\Kernel\Container
     */
    protected function addQuoteCreateBeforePlugins(Container $container): Container
    {
        $container->set(static::PLUGINS_QUOTE_CREATE_BEFORE, function (Container $container) {
            return $this->getQuoteCreateBeforePlugins();
        });

        return $container;
    }

    /**
     * @param \Spryker\Zed\Kernel\Container $container
     *
     * @return \Spryker\Zed\Kernel\Container
     */
    protected function addQuoteUpdateAfterPlugins(Container $container): Container
    {
        $container->set(static::PLUGINS_QUOTE_UPDATE_AFTER, function (Container $container) {
            return $this->getQuoteUpdateAfterPlugins();
        });

        return $container;
    }

    /**
     * @param \Spryker\Zed\Kernel\Container $container
     *
     * @return \Spryker\Zed\Kernel\Container
     */
    protected function addQuoteExpandBeforeCreatePlugins(Container $container): Container
    {
        $container->set(static::PLUGINS_QUOTE_EXPAND_BEFORE_CREATE, function (Container $container): array {
            return $this->getQuoteExpandBeforeCreatePlugins();
        });

        return $container;
    }

    /**
     * @param \Spryker\Zed\Kernel\Container $container
     *
     * @return \Spryker\Zed\Kernel\Container
     */
    protected function addQuoteUpdateBeforePlugins(Container $container): Container
    {
        $container->set(static::PLUGINS_QUOTE_UPDATE_BEFORE, function (Container $container) {
            return $this->getQuoteUpdateBeforePlugins();
        });

        return $container;
    }

    /**
     * @param \Spryker\Zed\Kernel\Container $container
     *
     * @return \Spryker\Zed\Kernel\Container
     */
    protected function addQuoteDeleteBeforePlugins(Container $container): Container
    {
        $container->set(static::PLUGINS_QUOTE_DELETE_BEFORE, function (Container $container) {
            return $this->getQuoteDeleteBeforePlugins();
        });

        return $container;
    }

    /**
     * @param \Spryker\Zed\Kernel\Container $container
     *
     * @return \Spryker\Zed\Kernel\Container
     */
    protected function addQuoteDeleteAfterPlugins(Container $container): Container
    {
        $container->set(static::PLUGINS_QUOTE_DELETE_AFTER, function (Container $container) {
            return $this->getQuoteDeleteAfterPlugins();
        });

        return $container;
    }

    /**
     * @param \Spryker\Zed\Kernel\Container $container
     *
     * @return \Spryker\Zed\Kernel\Container
     */
    protected function addQuoteValidatorPlugins(Container $container): Container
    {
        $container->set(static::PLUGINS_QUOTE_VALIDATOR, function (Container $container) {
            return $this->getQuoteValidatorPlugins();
        });

        return $container;
    }

    /**
     * @param \Spryker\Zed\Kernel\Container $container
     *
     * @return \Spryker\Zed\Kernel\Container
     */
    protected function addQuoteFieldsAllowedForSavingProviderPlugins(Container $container): Container
    {
        $container->set(static::PLUGINS_QUOTE_FIELDS_ALLOWED_FOR_SAVING_PROVIDER, function () {
            return $this->getQuoteFieldsAllowedForSavingProviderPlugins();
        });

        return $container;
    }

    /**
     * @return array<\Spryker\Zed\QuoteExtension\Dependency\Plugin\QuoteWritePluginInterface>
     */
    protected function getQuoteCreateAfterPlugins(): array
    {
        return [];
    }

    /**
     * @return array<\Spryker\Zed\QuoteExtension\Dependency\Plugin\QuoteExpanderPluginInterface>
     */
    protected function getQuoteExpanderPlugins(): array
    {
        return [];
    }

    /**
     * @return array<\Spryker\Zed\QuoteExtension\Dependency\Plugin\QuoteWritePluginInterface>
     */
    protected function getQuoteCreateBeforePlugins(): array
    {
        return [];
    }

    /**
     * @return array<\Spryker\Zed\QuoteExtension\Dependency\Plugin\QuoteWritePluginInterface>
     */
    protected function getQuoteUpdateAfterPlugins(): array
    {
        return [];
    }

    /**
     * @return array<\Spryker\Zed\QuoteExtension\Dependency\Plugin\QuoteExpandBeforeCreatePluginInterface>
     */
    protected function getQuoteExpandBeforeCreatePlugins(): array
    {
        return [];
    }

    /**
     * @return array<\Spryker\Zed\QuoteExtension\Dependency\Plugin\QuoteWritePluginInterface>
     */
    protected function getQuoteUpdateBeforePlugins(): array
    {
        return [];
    }

    /**
     * @return array<\Spryker\Zed\QuoteExtension\Dependency\Plugin\QuoteWritePluginInterface>
     */
    protected function getQuoteDeleteBeforePlugins(): array
    {
        return [];
    }

    /**
     * @return array<\Spryker\Zed\QuoteExtension\Dependency\Plugin\QuoteDeleteAfterPluginInterface>
     */
    protected function getQuoteDeleteAfterPlugins(): array
    {
        return [];
    }

    /**
     * @return array<\Spryker\Zed\QuoteExtension\Dependency\Plugin\QuoteValidatorPluginInterface>
     */
    protected function getQuoteValidatorPlugins(): array
    {
        return [];
    }

    /**
     * @return array<\Spryker\Zed\QuoteExtension\Dependency\Plugin\QuoteFieldsAllowedForSavingProviderPluginInterface>
     */
    protected function getQuoteFieldsAllowedForSavingProviderPlugins(): array
    {
        return [];
    }
}
