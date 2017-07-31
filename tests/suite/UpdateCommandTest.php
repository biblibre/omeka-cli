<?php

use OmekaCli\Command\InfoCommand;

use PHPUnit\Framework\TestCase;

require_once 'AbstractTest.php';

/**
 * @covers UpdateCommand
 */
final class UpdateCommandTest extends AbstractTest
{
    public function testIsOutputFormatOk()
    {
        $command = $this->getCommand('update');

        ob_start();
        $ans = $command->run(array(), array(), $this->application);
        ob_end_clean();

        $this->assertEquals(0 , $ans);
    }
}
