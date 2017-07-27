<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../FakeLogger.php';

/**
 * @covers Nothing
 */
abstract class AbstractTest extends TestCase
{
    protected $application;
    protected $fakeLogger;

    final protected function setUp()
    {
        if (class_exists('Zend_Registry'))
            $this->application = Zend_Registry::get('omeka-cli-application');
        else
            $this->markTestSkipped('Error: Zend_Registry not set.\n');
    }

    protected function getCommand($name)
    {
        $this->fakeLogger = new FakeLogger;
        $commands = $this->application->getCommandManager();
        $command = $commands->get($name);
        $command->setLogger($this->fakeLogger);

        return $command;
    }
}
