<?php

namespace OmekaCli\Test\Command\Plugin;

use OmekaCli\Sandbox\SandboxFactory;
use OmekaCli\Test\Command\TestCase;

class PluginActivateCommandTest extends TestCase
{
    public function testPluginActivate()
    {
        // Put plugin in deactivated state first
        $this->getSandbox()->execute(function () {
            $pluginLoader = \Zend_Registry::get('plugin_loader');
            $pluginBroker = \Zend_Registry::get('pluginbroker');
            $pluginInstaller = new \Omeka_Plugin_Installer(
                $pluginBroker,
                $pluginLoader
            );
            $plugin = $pluginLoader->getPlugin('Foo');
            if ($plugin && $plugin->active) {
                $pluginInstaller->deactivate($plugin);
            } elseif (!$plugin) {
                $plugin = new \Plugin();
                $plugin->name = 'Foo';
                $pluginIniReader = \Zend_Registry::get('plugin_ini_reader');
                $pluginIniReader->load($plugin);
                $pluginInstaller->install($plugin);
                $pluginInstaller->deactivate($plugin);
            }
        });

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
        return 'plugin-activate';
    }
}
