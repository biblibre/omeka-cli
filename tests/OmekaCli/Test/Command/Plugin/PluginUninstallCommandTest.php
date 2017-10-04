<?php

namespace OmekaCli\Test\Command\Plugin;

use OmekaCli\Test\Command\TestCase;
use OmekaCli\Sandbox\SandboxFactory;

class PluginUninstallCommandTest extends TestCase
{
    public function testPluginUninstall()
    {
        $pluginInstallCommand = $this->getCommand('plugin-install');
        $pluginInstallCommand->run(array(), array('Foo'));

        $foo_bar = $this->getSandbox()->execute(function () {
            return get_option('foo_bar');
        });
        $this->assertEquals('baz', $foo_bar);

        SandboxFactory::flush();

        $status = $this->command->run(array(), array('Foo'));

        $this->assertEquals(0, $status);
        $foo_bar = $this->getSandbox()->execute(function () {
            return get_option('foo_bar');
        });
        $this->assertNull($foo_bar);
    }

    protected function getCommandName()
    {
        return 'plugin-uninstall';
    }
}
