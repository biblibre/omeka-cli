<?php

namespace OmekaCli\Test\Command;

class VersionCommandTest extends TestCase
{
    protected function getCommandName()
    {
        return 'version';
    }

    public function testVersion()
    {
        ob_start();
        $status = $this->command->run(array(), array());
        $output = ob_get_clean();

        $this->assertEquals(0, $status);
        $this->assertEquals(OMEKACLI_VERSION . "\n", $output);
    }
}
