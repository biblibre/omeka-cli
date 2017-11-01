<?php

namespace OmekaCli\Test\Command\Plugin;

use OmekaCli\Sandbox\SandboxFactory;
use OmekaCli\Omeka\PluginInstaller;
use OmekaCli\Context\Context;
use OmekaCli\Test\Command\TestCase;

class PluginEnableCommandTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        $pluginInstaller = new PluginInstaller();
        $pluginInstaller->setContext(new Context(getenv('OMEKA_PATH')));
        try {
            $pluginInstaller->uninstall('Foo');
        } catch (\Exception $e) {
        }

        SandboxFactory::flush();
    }

    public function testPluginEnableWhenUninstalled()
    {
        $status = $this->command->run(array(), array('Foo'));

        $this->assertEquals(0, $status);

        $is_active = $this->getSandbox()->execute(function () {
            return plugin_is_active('Foo');
        });
        $this->assertTrue($is_active);
    }

    public function testPluginEnableWhenDisabled()
    {
        $pluginInstaller = new PluginInstaller();
        $pluginInstaller->setContext(new Context(getenv('OMEKA_PATH')));
        try {
            $pluginInstaller->enable('Foo');
            $pluginInstaller->disable('Foo');
        } catch (\Exception $e) {
        }

        SandboxFactory::flush();

        $status = $this->command->run(array(), array('Foo'));

        $this->assertEquals(0, $status);

        $is_active = $this->getSandbox()->execute(function () {
            return plugin_is_active('Foo');
        });
        $this->assertTrue($is_active);
    }

    protected function getCommandName()
    {
        return 'plugin-enable';
    }
}
