<?php

use OmekaCli\Application;
use OmekaCli\Command\OptionsCommand;

use PHPUnit\Framework\TestCase;

require_once 'AbstractTest.php';

/**
 * @covers OptionsCommand
 */
final class OptionsCommandTest extends AbstractTest
{
    protected $application;

    public function testTest()
    {
        $command = new OptionsCommand();

        ob_start();
        $command->run(array(), array(), $this->application);
        $output = ob_get_clean();

        $this->assertNotEmpty($output);
    }
}
