<?php

use OmekaCli\Application;
use OmekaCli\Command\OptionsCommand;

use PHPUnit\Framework\TestCase;

require_once 'TestsTemplate.php';

/**
 * @covers OptionsCommand
 */
final class OptionsCommandTest extends TestsTemplate
{
    protected $application;

    public function testIsOutputFormatOk()
    {
        $command = new OptionsCommand();

        ob_start();
        $command->run(array(), array(), $this->application);
        $output = ob_get_clean();

        $this->assertNotEmpty($output);
    }
}
