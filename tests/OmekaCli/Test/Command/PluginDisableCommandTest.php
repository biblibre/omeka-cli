<?php

namespace OmekaCli\Test\Command;

use OmekaCli\Context\Context;
use OmekaCli\Omeka\PluginInstaller;

class PluginDisableCommandTest extends TestCase
{
    protected $commandName = 'plugin-disable';

    protected function tearDown(): void
    {
        $this->uninstallPlugin('Foo');
    }

    public function testPluginDisable()
    {
        // Put plugin in activated state first
        $pluginInstaller = new PluginInstaller();
        $pluginInstaller->setContext(new Context(getenv('OMEKA_PATH')));
        try {
            $pluginInstaller->enable('Foo');
        } catch (\Exception $e) {
        }

        $this->flushSandboxes();

        $status = $this->commandTester->execute(['name' => 'Foo']);

        $this->assertEquals(0, $status);

        $is_active = $this->getSandbox()->execute(function () {
            return plugin_is_active('Foo');
        });
        $this->assertFalse($is_active);
    }
}
