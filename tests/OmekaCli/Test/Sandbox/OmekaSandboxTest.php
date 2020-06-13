<?php

namespace OmekaCli\Test\Sandbox;

use OmekaCli\Context\Context;
use OmekaCli\Sandbox\OmekaSandbox;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\StreamOutput;

class OmekaSandboxTest extends TestCase
{
    protected $sandbox;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sandbox = new OmekaSandbox();
        $this->sandbox->setContext(new Context(getenv('OMEKA_PATH')));
    }

    protected function tearDown(): void
    {
        unset($this->sandbox);
    }

    public function testSimpleCallback()
    {
        $a = 'a';
        $b = 'b';

        $return = $this->sandbox->execute(function () use ($a, $b) {
            define('SANDBOX_TEST', true);

            return array('foo' => $b . $a . 'r');
        });

        $this->assertEquals($return['foo'], 'bar');
        $this->assertFalse(defined('SANDBOX_TEST'));
    }

    public function testSimpleCallbackInShortLivedEnv()
    {
        $a = 'a';
        $b = 'b';

        $return = $this->sandbox->execute(function () use ($a, $b) {
            define('SANDBOX_TEST', true);

            return array('foo' => $b . $a . 'r');
        }, OmekaSandbox::ENV_SHORTLIVED);

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

    public function testOmekaEnvIsLoadedInShortLivedEnv()
    {
        $omekaVersionDefined = function () {
            return defined('OMEKA_VERSION');
        };

        $this->assertTrue($this->sandbox->execute($omekaVersionDefined, OmekaSandbox::ENV_SHORTLIVED));
        $this->assertFalse(call_user_func($omekaVersionDefined));
    }

    public function testFatalError()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Call to undefined function undefined_function()');
        $this->sandbox->execute(function () {
            \undefined_function();
        });
    }

    public function testFatalErrorInShortLivedEnv()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Call to undefined function undefined_function()');
        $this->sandbox->execute(function () {
            \undefined_function();
        }, OmekaSandbox::ENV_SHORTLIVED);
    }

    public function testLongLivedSandboxIsPersistent()
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

    public function testShortLivedSandboxIsNotPersistent()
    {
        $this->sandbox->execute(function () {
            $GLOBALS['foo'] = 'bar';
        }, OmekaSandbox::ENV_SHORTLIVED);

        $this->assertFalse(isset($GLOBALS['foo']));
        $foo = $this->sandbox->execute(function () {
            return @$GLOBALS['foo'];
        }, OmekaSandbox::ENV_SHORTLIVED);
        $this->assertNull($foo);
    }

    public function testResourcesCanBeUsedInShortLivedEnv()
    {
        $output = new StreamOutput(tmpfile());
        $this->sandbox->execute(function () use ($output) {
            $output->write('Test');
        }, OmekaSandbox::ENV_SHORTLIVED);

        $stream = $output->getStream();
        rewind($stream);
        $this->assertEquals('Test', stream_get_contents($stream));
    }
}
