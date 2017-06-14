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
        $command->run(array('get' => 1,),
                      array('0' => 'omeka_version',),
                      $this->application);
        $output = ob_get_clean();

        $this->assertNotEmpty($output);
    }

    public function testReturnEmptyLineOnNonExistingTableEntries()
    {
        $command = new OptionsCommand();

        ob_start();
        $command->run(array('get' => 1,),
                      array('0' => 'NonExistingTableEntries',),
                      $this->application);
        $output = ob_get_clean();

        $this->assertEquals($output, "\n");
    }

    public function testCanEditExistingTableEntries()
    {
        $command = new OptionsCommand();

        ob_start();
        $command->run(array('get' => 1,),
                      array('0' => 'omeka_version',),
                      $this->application);
        $oldVal = substr(ob_get_clean(), 0, -1);

        ob_start();
        $command->run(array('set' => 1,),
                      array('0' => 'omeka_version',
                            '1' => '0.0.0',),
                      $this->application);
        $output = ob_get_clean();

        ob_start();
        $command->run(array('set' => 1,),
                      array('0' => 'omeka_version',
                            '1' => $oldVal,),
                      $this->application);
        ob_end_clean();

        $this->assertNotEmpty($output, "\n");
    }
}
