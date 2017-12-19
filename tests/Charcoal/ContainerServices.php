<?php

namespace Charcoal\Tests;

use PDO;

// From Mockery
use Mockery;

// From Pimple
use Pimple\Container;

// From PSR-3
use Psr\Log\NullLogger;

// From Slim
use Slim\Http\Uri;

// From 'cache/void-adapter' (PSR-6)
use Cache\Adapter\Void\VoidCachePool;

// From 'tedivm/stash' (PSR-6)
use Stash\Pool;
use Stash\Driver\Ephemeral;

// From 'zendframework/zend-permissions-acl'
use Zend\Permissions\Acl\Acl;

// From 'league/climate'
use League\CLImate\CLImate;
use League\CLImate\Util\System\Linux;
use League\CLImate\Util\Output;
use League\CLImate\Util\Reader\Stdin;
use League\CLImate\Util\UtilFactory;

// From 'charcoal-factory'
use Charcoal\Factory\GenericFactory as Factory;

// From 'charcoal-app'
use Charcoal\App\AppConfig;
use Charcoal\App\Template\WidgetBuilder;

// From 'charcoal-core'
use Charcoal\Loader\CollectionLoader;
use Charcoal\Model\Service\MetadataLoader;
use Charcoal\Source\DatabaseSource;

// From 'charcoal-user'
use Charcoal\User\Authenticator;
use Charcoal\User\Authorizer;

// From 'charcoal-ui'
use Charcoal\Ui\Dashboard\DashboardBuilder;
use Charcoal\Ui\Dashboard\DashboardInterface;
use Charcoal\Ui\Layout\LayoutBuilder;
use Charcoal\Ui\Layout\LayoutFactory;

// From 'charcoal-email'
use Charcoal\Email\Email;
use Charcoal\Email\EmailConfig;

// From 'charcoal-view'
use Charcoal\View\ViewServiceProvider;
use Charcoal\View\Mustache\MustacheEngine;
use Charcoal\View\Mustache\MustacheLoader;

// From 'charcoal-translator'
use Charcoal\Translator\LocalesManager;
use Charcoal\Translator\Translator;

// From 'charcoal-admin'
use Charcoal\Admin\Config as AdminConfig;

/**
 *
 */
class ContainerServices
{
    /**
     * Setup the application's base URI.
     *
     * @param  Container $container A DI container.
     * @return void
     */
    public function registerBaseUrl(Container $container)
    {
        $container['base-url'] = function (Container $container) {
            return Uri::createFromString('');
        };
    }

    /**
     * Setup the application's base URI.
     *
     * @param  Container $container A DI container.
     * @return void
     */
    public function registerAdminBaseUrl(Container $container)
    {
        $container['admin/base-url'] = function (Container $container) {
            return Uri::createFromString('')->withBasePath($container['admin/config']['base_path']);
        };
    }

    /**
     * Setup the application configset.
     *
     * @param  Container $container A DI container.
     * @return void
     */
    public function registerConfig(Container $container)
    {
        $container['config'] = function (Container $container) {
            return new AppConfig([
                'base_path'  => realpath(__DIR__.'/../..'),
                'apis'       => [
                    'google' => [
                        'recaptcha' => [
                            'public_key'  => 'foobar',
                            'private_key' => 'bazqux',
                        ]
                    ]
                ]
            ]);
        };
    }

    /**
     * Setup the admin module configset.
     *
     * @param  Container $container A DI container.
     * @return void
     */
    public function registerAdminConfig(Container $container)
    {
        $container['admin/config'] = function (Container $container) {
            return new AdminConfig();
        };
    }

    public function registerLayoutFactory(Container $container)
    {
        $container['layout/factory'] = function (Container $container) {

            $layoutFactory = new LayoutFactory();
            return $layoutFactory;
        };
    }

    public function registerLayoutBuilder(Container $container)
    {
        $this->registerLayoutFactory($container);

        $container['layout/builder'] = function (Container $container) {
            $layoutFactory = $container['layout/factory'];
            $layoutBuilder = new LayoutBuilder($layoutFactory, $container);
            return $layoutBuilder;
        };
    }

    public function registerDashboardFactory(Container $container)
    {
        $container['dashboard/factory'] = function (Container $container) {
            return new Factory([
                'arguments'          => [[
                    'container'      => $container,
                    'logger'         => $container['logger'],
                    'widget_builder' => $container['widget/builder'],
                    'layout_builder' => $container['layout/builder']
                ]],
                'resolver_options' => [
                    'suffix' => 'Dashboard'
                ]
            ]);
        };
    }

    public function registerDashboardBuilder(Container $container)
    {
        $container['dashboard/builder'] = function (Container $container) {
            $dashboardFactory = $container['dashboard/factory'];
            $dashboardBuilder = new DashboardBuilder($dashboardFactory, $container);
            return $dashboardBuilder;
        };
    }

    public function registerWidgetFactory(Container $container)
    {
        $container['widget/factory'] = function (Container $container) {
            return new Factory([
                'resolver_options' => [
                    'suffix' => 'Widget'
                ],
                'arguments' => [[
                    'container' => $container,
                    'logger'    => $container['logger']
                ]]
            ]);
        };
    }

    public function registerWidgetBuilder(Container $container)
    {
        $container['widget/builder'] = function (Container $container) {
            return new WidgetBuilder($container['widget/factory'], $container);
        };
    }

    public function registerClimate(Container $container)
    {
        $container['climate/system'] = function (Container $container) {
            $system = Mockery::mock(Linux::class);
            $system->shouldReceive('hasAnsiSupport')->andReturn(true);
            $system->shouldReceive('width')->andReturn(80);

            return $system;
        };

        $container['climate/output'] = function (Container $container) {
            $output = Mockery::mock(Output::class);
            $output->shouldReceive('persist')->andReturn($output);
            $output->shouldReceive('sameLine')->andReturn($output);
            $output->shouldReceive('write');

            return $output;
        };

        $container['climate/reader'] = function (Container $container) {
            $reader = Mockery::mock(Stdin::class);
            $reader->shouldReceive('line')->andReturn('line');
            $reader->shouldReceive('char')->andReturn('char');
            $reader->shouldReceive('multiLine')->andReturn('multiLine');
            return $reader;
        };

        $container['climate/util'] = function (Container $container) {
            return new UtilFactory($container['climate/system']);
        };

        $container['climate'] = function (Container $container) {
            $climate = new CLImate();

            $climate->setOutput($container['climate/output']);
            $climate->setUtil($container['climate/util']);
            $climate->setReader($container['climate/reader']);

            return $climate;
        };
    }

    /**
     * Setup the framework's view renderer.
     *
     * @param  Container $container A DI container.
     * @return void
     */
    public function registerView(Container $container)
    {
        $container['view/loader'] = function (Container $container) {
            return new MustacheLoader([
                'logger'    => $container['logger'],
                'base_path' => $container['config']['base_path'],
                'paths'     => [
                    'views'
                ]
            ]);
        };

        $container['view/engine'] = function (Container $container) {
            return new MustacheEngine([
                'logger' => $container['logger'],
                'cache'  => MustacheEngine::DEFAULT_CACHE_PATH,
                'loader' => $container['view/loader']
            ]);
        };

        $container['view'] = function (Container $container) {
            return new GenericView([
                'logger' => $container['logger'],
                'engine' => $container['view/engine']
            ]);
        };
    }

    /**
     * Setup the application's translator service.
     *
     * @param  Container $container A DI container.
     * @return void
     */
    public function registerTranslator(Container $container)
    {
        $container['locales/manager'] = function (Container $container) {
            return new LocalesManager([
                'locales' => [
                    'en' => [ 'locale' => 'en-US' ]
                ]
            ]);
        };

        $container['translator'] = function (Container $container) {
            return new Translator([
                'manager' => $container['locales/manager']
            ]);
        };
    }

    /**
     * Setup the application's translator service.
     *
     * @param  Container $container A DI container.
     * @return void
     */
    public function registerMultilingualTranslator(Container $container)
    {
        $container['locales/manager'] = function (Container $container) {
            $manager = new LocalesManager([
                'locales' => [
                    'en'  => [
                        'locale' => 'en-US',
                        'name'   => [
                            'en' => 'English',
                            'fr' => 'Anglais',
                            'es' => 'Inglés'
                        ]
                    ],
                    'fr' => [
                        'locale' => 'fr-CA',
                        'name'   => [
                            'en' => 'French',
                            'fr' => 'Français',
                            'es' => 'Francés'
                        ]
                    ],
                    'de' => [
                        'locale' => 'de-DE'
                    ],
                    'es' => [
                        'locale' => 'es-MX'
                    ]
                ],
                'default_language'   => 'en',
                'fallback_languages' => [ 'en' ]
            ]);

            $manager->setCurrentLocale($manager->currentLocale());

            return $manager;
        };

        $container['translator'] = function (Container $container) {
            $translator = new Translator([
                'manager' => $container['locales/manager']
            ]);

            $translator->addLoader('array', new ArrayLoader());
            $translator->addResource('array', [ 'locale.de' => 'German'   ], 'en', 'messages');
            $translator->addResource('array', [ 'locale.de' => 'Allemand' ], 'fr', 'messages');
            $translator->addResource('array', [ 'locale.de' => 'Deutsch'  ], 'es', 'messages');
            $translator->addResource('array', [ 'locale.de' => 'Alemán'   ], 'de', 'messages');

            return $translator;
        };
    }

    /**
     * Setup the application's logging interface.
     *
     * @param  Container $container A DI container.
     * @return void
     */
    public function registerLogger(Container $container)
    {
        $container['logger'] = function (Container $container) {
            return new NullLogger();
        };
    }

    /**
     * Setup the application's caching interface.
     *
     * @param  Container $container A DI container.
     * @return void
     */
    public function registerCache(Container $container)
    {
        $container['cache'] = function ($container) {
            return new Pool(new Ephemeral());
        };
    }

    public function registerDatabase(Container $container)
    {
        $container['database'] = function (Container $container) {
            $pdo = new PDO('sqlite::memory:');
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $pdo;
        };
    }

    /**
     * Setup the framework's metadata loader interface.
     *
     * @param  Container $container A DI container.
     * @return void
     */
    public function registerMetadataLoader(Container $container)
    {
        $container['metadata/loader'] = function (Container $container) {
            return new MetadataLoader([
                'logger'    => $container['logger'],
                'cache'     => $container['cache'],
                'base_path' => $container['config']['base_path'],
                'paths'     => [
                    'metadata',
                    'vendor/locomotivemtl/charcoal-object/metadata',
                    'vendor/locomotivemtl/charcoal-user/metadata'
                ]
            ]);
        };
    }

    /**
     * Setup the framework's data source factory.
     *
     * @param  Container $container A DI container.
     * @return void
     */
    public function registerSourceFactory(Container $container)
    {
        $container['source/factory'] = function (Container $container) {
            return new Factory([
                'map' => [
                    'database' => DatabaseSource::class
                ],
                'arguments'  => [[
                    'logger' => $container['logger'],
                    'pdo'    => $container['database']
                ]]
            ]);
        };
    }

    /**
     * Setup the framework's property factory.
     *
     * @param  Container $container A DI container.
     * @return void
     */
    public function registerPropertyFactory(Container $container)
    {
        $container['property/factory'] = function (Container $container) {
            return new Factory([
                'resolver_options' => [
                    'prefix' => '\\Charcoal\\Property\\',
                    'suffix' => 'Property'
                ],
                'arguments' => [[
                    'container'  => $container,
                    'database'   => $container['database'],
                    'translator' => $container['translator'],
                    'logger'     => $container['logger']
                ]]
            ]);
        };
    }

    public function registerPropertyDisplayFactory(Container $container)
    {
        $container['property/display/factory'] = function (Container $container) {
            return new Factory([
                'resolver_options' => [
                    'suffix' => 'Display'
                ],
                'arguments' => [[
                    'container' => $container,
                    'logger'    => $container['logger']
                ]]
            ]);
        };
    }

    /**
     * Setup the framework's model factory.
     *
     * @param  Container $container A DI container.
     * @return void
     */
    public function registerModelFactory(Container $container)
    {
        $container['model/factory'] = function (Container $container) {
            return new Factory([
                'arguments' => [[
                    'container'        => $container,
                    'logger'           => $container['logger'],
                    'metadata_loader'  => $container['metadata/loader'],
                    'property_factory' => $container['property/factory'],
                    'source_factory'   => $container['source/factory']
                ]]
            ]);
        };
    }

    public function registerAcl(Container $container)
    {
        $container['admin/acl'] = function (Container $container) {
            return new Acl();
        };
    }

    public function registerAuthenticator(Container $container)
    {
        $container['admin/authenticator'] = function (Container $container) {
            return new Authenticator([
                'logger'        => $container['logger'],
                'user_type'     => 'charcoal/admin/user',
                'user_factory'  => $container['model/factory'],
                'token_type'    => 'charcoal/admin/user/auth-token',
                'token_factory' => $container['model/factory']
            ]);
        };
    }

    public function registerAuthorizer(Container $container)
    {
        $container['admin/authorizer'] = function (Container $container) {
            return new Authorizer([
                'logger'    => $container['logger'],
                'acl'       => $container['admin/acl'],
                'resource'  => 'admin'
            ]);
        };
    }

    /**
     * Setup the framework's collection loader interface.
     *
     * @param  Container $container A DI container.
     * @return void
     */
    public function registerModelCollectionLoader(Container $container)
    {
        $container['model/collection/loader'] = function (Container $container) {
            return new CollectionLoader([
                'logger' => $container['logger'],
                'factory' => $container['model/factory']
            ]);
        };
    }

    public function registerEmailFactory(Container $container)
    {
        $container['email/factory'] = function (Container $container) {
            return new Factory([
                'map' => [
                    'email' => Email::class
                ]
            ]);
        };
    }

    public function registerElfinderConfig(Container $container)
    {
        $container['elfinder/config'] = function (Container $container) {
            return [];
        };
    }

    public function registerAdminMenu(Container $container)
    {
        $container['menu/builder'] = null;
        $container['menu/item/builder'] = null;
    }

    public function registerAdminSidemenu(Container $container)
    {
    }
}
