<?php

namespace OmekaCli\Test\Command;

use OmekaCli\Context\Context;
use OmekaCli\Omeka\PluginInstaller;

class PluginEnableCommandTest extends TestCase
{
    protected $commandName = 'plugin-enable';

    public function setUp()
    {
        parent::setUp();

        $pluginInstaller = new PluginInstaller();
        $pluginInstaller->setContext(new Context(getenv('OMEKA_PATH')));
        try {
            $pluginInstaller->uninstall('Foo');
        } catch (\Exception $e) {
        }

        $this->flushSandboxes();
    }

    public function testPluginEnableWhenUninstalled()
    {
        $status = $this->commandTester->execute(array('name' => 'Foo'));

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

        $this->flushSandboxes();

        $status = $this->commandTester->execute(array('name' => 'Foo'));

        $this->assertEquals(0, $status);

        $is_active = $this->getSandbox()->execute(function () {
            return plugin_is_active('Foo');
        });
        $this->assertTrue($is_active);
    }
}
