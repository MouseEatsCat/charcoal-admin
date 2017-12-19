<?php

namespace Charcoal\Tests\Admin\Widget;

use Charcoal\Admin\Widget\Graph\AbstractGraphWidget;

class GraphWidgetTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $logger = new \Psr\Log\NullLogger();
        $this->obj = $this->getMockForAbstractClass('\Charcoal\Admin\Widget\Graph\AbstractGraphWidget', [[
            'logger'=>$logger
        ]]);
    }

    public function testSetData()
    {
        $obj = $this->obj;
        $ret = $obj->setData([
            'height'=>222,
            'colors'=>['#ff0000', '#0000ff']
        ]);
        $this->assertSame($obj, $ret);
        $this->assertEquals(222, $obj->height());
        $this->assertEquals(['#ff0000', '#0000ff'], $obj->colors());
    }

    public function testSetHeight()
    {
        $obj =  $this->obj;
        $this->assertEquals(400, $obj->height());

        $ret = $obj->setHeight(333);
        $this->assertSame($obj, $ret);
        $this->assertEquals(333, $obj->height());

        //$this->setExpectedException('\InvalidArgumentException');
        //$obj->setHeight(false);
    }

    public function testSetColors()
    {
        $obj =  $this->obj;
        $this->assertEquals($obj->defaultColors(), $obj->colors());

        $ret = $obj->setColors(['#fff', '#000']);
        $this->assertSame($ret, $obj);
        $this->assertEquals(['#fff', '#000'], $obj->colors());
    }
}
