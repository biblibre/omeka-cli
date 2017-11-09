<?php

namespace OmekaCli\Test\Command;

class ListCommandTest extends TestCase
{
    public function testOutput()
    {
        ob_start();
        $status = $this->command->run(array(), array());
        $output = ob_get_clean();

        $this->assertEquals(0, $status);
        $this->assertRegExp('/version -- print version of omeka-cli/', $output);
        $this->assertRegExp('/list -- list available commands/', $output);
        $this->assertRegExp('/plugin-download -- download a plugin \(aliases: dl\)/', $output);
    }

    protected function getCommandName()
    {
        return 'list';
    }
}
