<?php

namespace OmekaCli\Test\Command;

abstract class TestCase extends \OmekaCli\Test\TestCase
{
    protected $command;

    public function setUp()
    {
        parent::setUp();

        $this->command = $this->getCommand($this->getCommandName());
    }

    abstract protected function getCommandName();
}
