<?php
//
//namespace Charcoal\Tests\Admin\Widget;
//
//use PHPUnit_Framework_TestCase;
//
//use \Psr\Log\NullLogger;
//
//use Pimple\Container;
//
//use \Charcoal\Admin\Widget\MapWidget;
//
//use Charcoal\Tests\Admin\ContainerProvider;
//
//class MapWidgetTest extends PHPUnit_Framework_TestCase
//{
//    public function setUp()
//    {
//        $container = new Container();
//        $containerProvider = new ContainerProvider();
//        $containerProvider->registerWidgetDependencies($container);
//
//        $this->obj = new MapWidget([
//            'logger' => $container['logger'],
//            'container' => $container
//        ]);
//    }
//
//    public function testConstructor()
//    {
//        $this->assertInstanceOf(MapWidget::class, $this->obj);
//    }
//}
