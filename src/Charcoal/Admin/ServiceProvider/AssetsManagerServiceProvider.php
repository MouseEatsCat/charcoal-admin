<?php

namespace Charcoal\Admin\ServiceProvider;

// from pimple
use Charcoal\Admin\AssetsConfig;
use Charcoal\Admin\Service\AssetsBuilder;
use Pimple\Container;
use Pimple\ServiceProviderInterface;

use Assetic\AssetManager;

/**
 * Class AssetsManagerServiceProvider
 */
class AssetsManagerServiceProvider implements ServiceProviderInterface
{
    /**
     * Registers services on the given container.
     *
     * This method should only be used to configure services and parameters.
     * It should not get services.
     *
     * @param Container $container A container instance
     * @return void
     */
    public function register(Container $container)
    {
        $this->registerAssetsManager($container);
    }

    /**
     * Registers services for {@link https://selectize.github.io/selectize.js/ Selectize}.
     *
     * @param  Container $container The Pimple DI Container.
     * @return void
     */
    protected function registerAssetsManager(Container $container)
    {
        $container['assets/config'] = function (Container $container) {
            $config = $container['view/config']->get('assets');

            return new AssetsConfig($config);
        };

        $container['assets/builder'] = function (Container $container) {
            return new AssetsBuilder();
        };

        /**
         * @param Container $container Pimple DI container.
         * @return AssetManager
         */
        $container['assets'] = function (Container $container) {
            $assetsBuilder = $container['assets/builder'];
            $assetsConfig = $container['assets/config'];

            return $assetsBuilder($assetsConfig);
        };
    }

}
