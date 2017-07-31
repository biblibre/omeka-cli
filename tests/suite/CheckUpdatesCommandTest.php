<?php

use OmekaCli\Command\CheckUpdatesCommand;

use PHPUnit\Framework\TestCase;

require_once 'AbstractTest.php';

/**
 * @covers CheckUpdateCommand
 */
final class CheckUpdatesCommandTest extends AbstractTest
{
    public function testIsOutputFormatOk()
    {
        $command = $this->getCommand('check-updates');

        ob_start();
        $ans = $command->run(array(), array(), $this->application);
        ob_end_clean();

        $this->assertEquals(0 , $ans);
    }
}
