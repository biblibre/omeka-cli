<?php

namespace OmekaCli\Test\Sandbox;

use OmekaCli\Sandbox\OmekaSandbox;
use OmekaCli\Context\Context;
use PHPUnit\Framework\TestCase;

class OmekaSandboxTest extends TestCase
{
    protected $sandbox;

    public function setUp()
    {
        parent::setUp();

        $this->sandbox = new OmekaSandbox();
        $this->sandbox->setContext(new Context(getenv('OMEKA_PATH')));
    }

    public function tearDown()
    {
        unset($this->sandbox);
    }

    public function testSimpleCallback()
    {
        $b = 'b';

        $return = $this->sandbox->execute(function ($a) use ($b) {
            define('SANDBOX_TEST', true);

            return array('foo' => $b . $a . 'r');
        }, 'a');

        $this->assertEquals($return['foo'], 'bar');
        $this->assertFalse(defined('SANDBOX_TEST'));
    }

    public function testOmekaEnvIsLoaded()
    {
        $omekaVersionDefined = function () {
            return defined('OMEKA_VERSION');
        };

        $this->assertTrue($this->sandbox->execute($omekaVersionDefined));
        $this->assertFalse(call_user_func($omekaVersionDefined));
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Call to undefined function undefined_function()
     */
    public function testFatalError()
    {
        $this->sandbox->execute(function () {
            undefined_function();
        });
    }

    public function testSandboxIsPersistent()
    {
        $this->sandbox->execute(function () {
            $GLOBALS['foo'] = 'bar';
        });

        $this->assertFalse(isset($GLOBALS['foo']));
        $foo = $this->sandbox->execute(function () {
            return $GLOBALS['foo'];
        });
        $this->assertEquals('bar', $foo);
    }
}
