<?php

namespace OmekaCli\Test\Command\Plugin;

use OmekaCli\Sandbox\SandboxFactory;
use OmekaCli\Omeka\PluginInstaller;
use OmekaCli\Context\Context;
use OmekaCli\Test\Command\TestCase;

class PluginDisableCommandTest extends TestCase
{
    public function testPluginDisable()
    {
        // Put plugin in activated state first
        $pluginInstaller = new PluginInstaller();
        $pluginInstaller->setContext(new Context(getenv('OMEKA_PATH')));
        try {
            $pluginInstaller->enable('Foo');
        } catch (\Exception $e) {
        }

        SandboxFactory::flush();

        $status = $this->command->run(array(), array('Foo'));

        $this->assertEquals(0, $status);

        $is_active = $this->getSandbox()->execute(function () {
            return plugin_is_active('Foo');
        });
        $this->assertFalse($is_active);
    }

    protected function getCommandName()
    {
        return 'plugin-disable';
    }
}
