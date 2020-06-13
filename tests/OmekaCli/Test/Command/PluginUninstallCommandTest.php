<?php

namespace OmekaCli\Test\Command;

class PluginUninstallCommandTest extends TestCase
{
    protected $commandName = 'plugin-uninstall';

    protected function setUp(): void
    {
        parent::setUp();

        $this->installPlugin('Foo');

        $this->flushSandboxes();
    }

    protected function tearDown(): void
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

        $status = $this->commandTester->execute(['name' => 'Foo']);

        $this->assertEquals(0, $status);
        $foo_bar = $this->getSandbox()->execute(function () {
            return get_option('foo_bar');
        });
        $this->assertNull($foo_bar);
    }
}
