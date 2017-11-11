<?php

namespace OmekaCli\Test\Command;

use Symfony\Component\Console\Tester\CommandTester;

abstract class TestCase extends \OmekaCli\Test\TestCase
{
    protected $commandName;
    protected $commandTester;

    public function setUp()
    {
        parent::setUp();

        $command = $this->getCommand($this->commandName);
        $this->commandTester = new CommandTester($command);
    }

    protected function getCommand($name)
    {
        $command = $this->application->get($name);
        $command->setHelperSet($this->application->getHelperSet());

        return $command;
    }
}
