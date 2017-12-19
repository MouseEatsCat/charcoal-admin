<?php

namespace Charcoal\Tests\Admin\Widget;

// From PHPUnit
use PHPUnit_Framework_TestCase;

use Psr\Log\NullLogger;

// From Pimple
use Pimple\Container;

// From 'charcoal-admin'
use Charcoal\Admin\Widget\TableWidget;
use Charcoal\Tests\Admin\ContainerProvider;

/**
 *
 */
class TableWidgetTest extends PHPUnit_Framework_TestCase
{
    /**
     * Tested Class.
     *
     * @var TableWidget
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

        $this->obj = new TableWidget([
            'logger'    => $container['logger'],
            'container' => $container
        ]);
    }

    public function testSetSortable()
    {
        $ret = $this->obj->setSortable(true);
        $this->assertSame($ret, $this->obj);
        $this->assertTrue($this->obj->sortable());

        $this->obj['sortable'] = false;
        $this->assertFalse($this->obj->sortable());

        $this->obj->set('sortable', true);
        $this->assertTrue($this->obj['sortable']);
    }

    public function testShowTableHeader()
    {
        $this->assertTrue($this->obj->showTableHeader());
        $ret = $this->obj->setShowTableHeader(false);
        $this->assertSame($ret, $this->obj);
        $this->assertFalse($this->obj->showTableHeader());

        $this->obj['show_table_header'] = true;
        $this->assertTrue($this->obj->showTableHeader());

        $this->obj->set('show_table_header', false);
        $this->assertFalse($this->obj['show_table_header']);
    }

    public function testShowTableHead()
    {
        $this->assertTrue($this->obj->showTableHead());
        $ret = $this->obj->setShowTableHead(false);
        $this->assertSame($ret, $this->obj);
        $this->assertFalse($this->obj->showTableHead());

        $this->obj['show_table_head'] = true;
        $this->assertTrue($this->obj->showTableHead());

        $this->obj->set('show_table_head', false);
        $this->assertFalse($this->obj['show_table_head']);
    }

    public function testShowTableFoot()
    {
        $this->assertFalse($this->obj->showTableFoot());
        $ret = $this->obj->setShowTableFoot(false);
        $this->assertSame($ret, $this->obj);
        $this->assertFalse($this->obj->showTableFoot());

        $this->obj['show_table_foot'] = false;
        $this->assertFalse($this->obj->showTableFoot());

        $this->obj->set('show_table_foot', true);
        $this->assertTrue($this->obj['show_table_foot']);
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
            $containerProvider->registerCollectionLoader($container);
            $containerProvider->registerWidgetDependencies($container);
            $containerProvider->registerWidgetFactory($container);
            $containerProvider->registerPropertyFactory($container);
            $containerProvider->registerPropertyDisplayFactory($container);

            $container['view'] = $this->createMock('\Charcoal\View\ViewInterface');

            $this->container = $container;
        }

        return $this->container;
    }
}
