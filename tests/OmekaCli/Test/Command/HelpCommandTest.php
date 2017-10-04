<?php

namespace OmekaCli\Test\Command;

class HelpCommandTest extends TestCase
{
    public function testNoArgs()
    {
        ob_start();
        $status = $this->command->run(array(), array());
        $output = ob_get_clean();

        $this->assertEquals(0, $status);
        $this->assertRegExp('/Usage/', $output);
    }

    public function testUnknownCommand()
    {
        $status = $this->command->run(array(), array('none'));

        $this->assertEquals(1, $status);
        $this->assertRegExp('/Command none does not exist/', $this->logger->getOutput());
    }

    public function testKnownCommand()
    {
        ob_start();
        $status = $this->command->run(array(), array('help'));
        $output = ob_get_clean();

        $this->assertEquals(0, $status);
        $this->assertRegExp('/help - print help for a specific command/', $output);
    }

    protected function getCommandName()
    {
        return 'help';
    }
}
