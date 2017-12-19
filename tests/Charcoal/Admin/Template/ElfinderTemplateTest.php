<?php

namespace Charcoal\Tests\Admin\Template;

use ReflectionClass;

// From PHPUnit
use PHPUnit_Framework_TestCase;

// From Pimple
use Pimple\Container;

// From 'charcoal-admin'
use Charcoal\Admin\Template\ElfinderTemplate;
use Charcoal\Tests\Admin\ContainerProvider;

/**
 *
 */
class ElfinderTemplateTest extends PHPUnit_Framework_TestCase
{
    /**
     * Tested Class.
     *
     * @var ElfinderTemplate
     */
    private $obj;

    /**
     * Store the service container.
     *
     * @var Container
     */
    private $container;

    /**
     * Set up the test.
     */
    public function setUp()
    {
        $container = $this->container();

        $this->obj = new ElfinderTemplate([
            'logger'    => $container['logger'],
            'container' => $container
        ]);
    }

    public function testAdminAssertsUrl()
    {
        $ret = $this->obj->adminAssetsUrl();
        $this->assertEquals('/assets/admin/', $ret);
    }

    /**
     * Set up the service container.
     *
     * @return Container
     */
    private function container()
    {
        if ($this->container === null) {
            $container = new Container();
            $containerProvider = new ContainerProvider();
            $containerProvider->registerTemplateDependencies($container);
            $containerProvider->registerMetadataLoader($container);
            $containerProvider->registerElfinderConfig($container);
            $container['widget/factory'] = $this->createMock('\Charcoal\Factory\FactoryInterface');

            $this->container = $container;
        }

        return $this->container;
    }
}
