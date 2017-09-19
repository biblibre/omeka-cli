<?php

namespace OmekaCli\Test\Command;

use OmekaCli\Test\TestCase;

class CheckUpdatesCommandTest extends TestCase
{
    public function testIsOutputFormatOk()
    {
        $command = $this->getCommand('check-updates');

        ob_start();
        $ans = $command->run(array(), array(), $this->application);
        ob_end_clean();

        $this->assertEquals(0, $ans);
    }
}
