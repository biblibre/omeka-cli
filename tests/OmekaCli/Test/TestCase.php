<?php

namespace OmekaCli\Test;

use Zend_Registry;
use OmekaCli\Test\Mock\LoggerMock;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    protected $application;
    protected $logger;

    protected function setUp()
    {
        if (class_exists('Zend_Registry')) {
            $this->application = Zend_Registry::get('omeka-cli-application');
        } else {
            $this->markTestSkipped('Error: Zend_Registry not set.\n');
        }
    }

    protected function getCommand($name)
    {
        $this->logger = new LoggerMock();
        $commands = $this->application->getCommandManager();
        $command = $commands->get($name);
        $command->setLogger($this->logger);

        return $command;
    }
}
