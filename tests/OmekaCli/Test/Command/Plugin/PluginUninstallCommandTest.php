<?php

namespace OmekaCli\Test\Command\Plugin;

use OmekaCli\Context\Context;
use OmekaCli\Omeka\PluginInstaller;
use OmekaCli\Test\Command\TestCase;

class PluginUninstallCommandTest extends TestCase
{
    protected $commandName = 'plugin-uninstall';

    public function setUp()
    {
        parent::setUp();

        $pluginInstaller = new PluginInstaller();
        $pluginInstaller->setContext(new Context(getenv('OMEKA_PATH')));
        try {
            $pluginInstaller->enable('Foo');
        } catch (\Exception $e) {
        }

        $this->flushSandboxes();
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
