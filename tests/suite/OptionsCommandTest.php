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

    public function testShowUsageWhenRunWithoutArgument()
    {
        $command = new OptionsCommand();

        ob_start();
        $command->run(array(), array(), $this->application);
        $output = ob_get_clean();

        $this->assertNotEmpty($output);
        $this->assertRegExp('
/\AUsage:
\toptions .+

.+

((.+)\n)*\z/', $output);
    }

    public function testCanRetrieveExistingTableEntries()
    {
        $command = new OptionsCommand();

        ob_start();
        $command->run(array(),
                      array('omeka_version',),
                      $this->application);
        $output = ob_get_clean();

        $this->assertNotEmpty($output);
    }

    public function testShowErrorOnNonExistingTableEntries()
    {
        $command = new OptionsCommand();

        ob_start();
        $command->run(array(),
                      array('NonExistingTableEntries',),
                      $this->application);
        $output = ob_get_clean();

        $this->assertRegExp('/Error: option .* not found./', $output);
    }

    public function testCanEditExistingTableEntries()
    {
        $command = new OptionsCommand();

        ob_start();
        $command->run(array(),
                      array('omeka_version',),
                      $this->application);
        $oldVal = substr(ob_get_clean(), 0, -1);

        ob_start();
        $command->run(array(),
                      array('omeka_version', '0.0.0',),
                      $this->application);
        $output = ob_get_clean();

        ob_start();
        $command->run(array(),
                      array('omeka_version', $oldVal,),
                      $this->application);
        ob_end_clean();

        $this->assertNotEmpty($output, "\n");
    }
}
