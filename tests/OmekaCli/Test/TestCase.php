<?php

namespace OmekaCli\Test;

use OmekaCli\Application;
use OmekaCli\Context\Context;
use OmekaCli\Sandbox\OmekaSandbox;
use OmekaCli\Sandbox\OmekaSandboxPool;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    protected $application;

    public function setUp()
    {
        $this->application = new Application();

        $context = new Context(getenv('OMEKA_PATH'));
        $this->application->getHelperSet()->get('context')->setContext($context);
    }

    protected function getSandbox()
    {
        return $this->application->getHelperSet()->get('context')->getSandbox();
    }

    protected function getNewSandbox()
    {
        $sandbox = new OmekaSandbox();
        $sandbox->setContext(new Context(getenv('OMEKA_PATH')));

        return $sandbox;
    }

    protected function flushSandboxes()
    {
        OmekaSandboxPool::flush();
    }

    protected function installPlugin($name)
    {
        $this->getNewSandbox()->execute(function () use ($name) {
            $pluginLoader = \Zend_Registry::get('plugin_loader');
            $plugin = $pluginLoader->getPlugin($name);
            if (!$plugin) {
                $plugin = new \Plugin();
                $plugin->name = $name;
                \Zend_Registry::get('plugin_ini_reader')->load($plugin);
                (new \Omeka_Plugin_Installer(
                    \Zend_Registry::get('pluginbroker'),
                    $pluginLoader
                ))->install($plugin);
            }
        });
    }

    protected function uninstallPlugin($name)
    {
        $this->pluginAction('uninstall', $name);
    }

    protected function activatePlugin($name)
    {
        $this->pluginAction('activate', $name);
    }

    protected function deactivatePlugin($name)
    {
        $this->pluginAction('deactivate', $name);
    }

    private function pluginAction($action, $name)
    {
        $this->getNewSandbox()->execute(function () use ($action, $name) {
            $pluginLoader = \Zend_Registry::get('plugin_loader');
            $plugin = $pluginLoader->getPlugin($name);
            if ($plugin) {
                (new \Omeka_Plugin_Installer(
                    \Zend_Registry::get('pluginbroker'),
                    $pluginLoader
                ))->$action($plugin);
            }
        });
    }
}
