<?php

namespace OmekaCli\Test\Command\Plugin;

use OmekaCli\Context\Context;
use OmekaCli\Omeka\PluginInstaller;
use OmekaCli\Test\Command\TestCase;

class PluginDisableCommandTest extends TestCase
{
    protected $commandName = 'plugin-disable';

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

        $status = $this->commandTester->execute(array('name' => 'Foo'));

        $this->assertEquals(0, $status);

        $is_active = $this->getSandbox()->execute(function () {
            return plugin_is_active('Foo');
        });
        $this->assertFalse($is_active);
    }
}
