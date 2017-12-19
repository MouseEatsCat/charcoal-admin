<?php

namespace Charcoal\Tests\Admin;

// From PHPUnit
use PHPUnit_Framework_TestCase;

// From PSR-7
use Psr\Http\Message\RequestInterface;

// From Pimple
use Pimple\Container;

// From 'charcoal-admin'
use Charcoal\Admin\AdminTemplate;

use Charcoal\Tests\Admin\ContainerProvider;

class AdminTemplateTest extends PHPUnit_Framework_TestCase
{
    /**
     * Tested Class.
     *
     * @var AdminTemplate
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

        $this->obj = new AdminTemplate([
            'logger'    => $container['logger'],
            'container' => $container
        ]);
    }

    public static function getMethod($obj, $name)
    {
        $class = new \ReflectionClass($obj);
        $method = $class->getMethod($name);
        $method->setAccessible(true);
        return $method;
    }

    public function testSetIdent()
    {
        $this->assertNull($this->obj->ident());
        $ret = $this->obj->setIdent('foobar');
        $this->assertSame($this->obj, $ret);
        $this->assertEquals('foobar', $this->obj->ident());
    }

    public function testSetLabel()
    {
        $this->assertNull($this->obj->label());
        $ret = $this->obj->setLabel('foobar');
        $this->assertSame($this->obj, $ret);
        $this->assertEquals('foobar', (string)$this->obj->label());
    }

    public function testAuthRequiredIsTrue()
    {
        $foo = self::getMethod($this->obj, 'authRequired');
        $res = $foo->invoke($this->obj);
        $this->assertTrue($res);
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
            $containerProvider->registerCollectionLoader($container);

            $container['widget/factory'] = $this->createMock('\Charcoal\Factory\FactoryInterface');

            $this->container = $container;
        }

        return $this->container;
    }
}
