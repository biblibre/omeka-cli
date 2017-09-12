<?php

require_once 'AbstractTest.php';

final class CheckUpdatesCommandTest extends AbstractTest
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
