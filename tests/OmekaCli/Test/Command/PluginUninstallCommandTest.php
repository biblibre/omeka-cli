<?php

namespace OmekaCli\Test\Command;

class PluginUninstallCommandTest extends TestCase
{
    protected $commandName = 'plugin-uninstall';

    public function setUp()
    {
        parent::setUp();

        $this->installPlugin('Foo');

        $this->flushSandboxes();
    }

    public function tearDown()
    {
        $this->uninstallPlugin('Foo');
    }

    public function testPluginUninstall()
    {
        $foo_bar = $this->getSandbox()->execute(function () {
            return get_option('foo_bar');
        });
        $this->assertEquals('baz', $foo_bar);

        $this->flushSandboxes();

        $status = $this->commandTester->execute(array('name' => 'Foo'));

        $this->assertEquals(0, $status);
        $foo_bar = $this->getSandbox()->execute(function () {
            return get_option('foo_bar');
        });
        $this->assertNull($foo_bar);
    }
}
