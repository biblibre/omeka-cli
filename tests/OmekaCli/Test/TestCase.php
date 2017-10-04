<?php

namespace OmekaCli\Test;

use OmekaCli\Application;
use OmekaCli\Context\Context;
use OmekaCli\Sandbox\OmekaSandbox;
use OmekaCli\Sandbox\SandboxFactory;
use OmekaCli\Test\Mock\LoggerMock;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    protected $application;
    protected $logger;

    public function setUp()
    {
        $this->application = $this->createApplication();
        $this->application->initialize();
    }

    protected function createApplication()
    {
        return new Application(array('omeka-path' => getenv('OMEKA_PATH')));
    }

    protected function getCommand($name)
    {
        $this->logger = new LoggerMock();
        $commands = $this->application->getCommandManager();
        $command = $commands->getCommand($name);
        $command->setLogger($this->logger);

        return $command;
    }

    protected function getSandbox()
    {
        return SandboxFactory::getSandbox(new Context(getenv('OMEKA_PATH')));
    }

    protected function getNewSandbox()
    {
        $sandbox = new OmekaSandbox();
        $sandbox->setContext(new Context(getenv('OMEKA_PATH')));

        return $sandbox;
    }

    protected function installPlugin($name)
    {
        $this->getNewSandbox()->execute(function () use ($name) {
            $plugin = new \Plugin();
            $plugin->name = $name;
            \Zend_Registry::get('plugin_ini_reader')->load($plugin);
            (new \Omeka_Plugin_Installer(
                \Zend_Registry::get('pluginbroker'),
                \Zend_Registry::get('plugin_loader')
            ))->install($plugin);
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
            (new \Omeka_Plugin_Installer(
                \Zend_Registry::get('pluginbroker'),
                $pluginLoader
            ))->$action($pluginLoader->getPlugin($name));
        });
    }
}
