<?php

namespace OmekaCli\Test\Command\Plugin;

use OmekaCli\Context\Context;
use OmekaCli\Omeka\PluginInstaller;
use OmekaCli\Test\Command\TestCase;
use OmekaCli\Sandbox\SandboxFactory;

class PluginUninstallCommandTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        $pluginInstaller = new PluginInstaller();
        $pluginInstaller->setContext(new Context(getenv('OMEKA_PATH')));
        try {
            $pluginInstaller->enable('Foo');
        } catch (\Exception $e) {
        }

        SandboxFactory::flush();
    }

    public function testPluginUninstall()
    {
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
