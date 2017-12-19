<?php

namespace Charcoal\Tests\Admin\Template\Account;

use ReflectionClass;

use Psr\Log\NullLogger;

use PHPUnit_Framework_TestCase;

use Charcoal\Admin\Template\Account\ResetPasswordTemplate;

/**
 *
 */
class ResetPasswordTemplateTest extends PHPUnit_Framework_TestCase
{
    /**
     * Instance of object under test
     * @var LoginTemplate
     */
    private $obj;

    public function setUp()
    {
        $this->obj = new ResetPasswordTemplate([
            'logger' => new NullLogger()
        ]);
    }

    public static function getMethod($obj, $name)
    {
        $class = new ReflectionClass($obj);
        $method = $class->getMethod($name);
        $method->setAccessible(true);
        return $method;
    }

    public function testAuthRequiredIsFalse()
    {

        $foo = self::getMethod($this->obj, 'authRequired');
        $res = $foo->invoke($this->obj);
        $this->assertNotTrue($res);
    }
}
