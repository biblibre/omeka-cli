<?php

use OmekaCli\Command\OptionsCommand;

use PHPUnit\Framework\TestCase;

require_once 'AbstractTest.php';

/**
 * @covers OptionsCommand
 */
final class OptionsCommandTest extends AbstractTest
{
    public function testShowUsageWhenRunWithoutArgument()
    {
        $command = $this->getCommand('options');

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
        $command = $this->getCommand('options');

        ob_start();
        $command->run(array(),
                      array('omeka_version',),
                      $this->application);
        $output = ob_get_clean();

        $this->assertNotEmpty($output);
    }

    public function testShowErrorOnNonExistingTableEntries()
    {
        $command = $this->getCommand('options');

        $retCode = $command->run(array(),
                      array('NonExistingTableEntries',),
                      $this->application);

        $this->assertEquals(1, $retCode);
        $this->assertRegExp('/Error: option .* not found./', $this->fakeLogger->getOutput());
    }

    public function testCanEditExistingTableEntries()
    {
        $command = $this->getCommand('options');

        ob_start();
        $command->run(array(),
                      array('omeka_version'),
                      $this->application);
        $oldVal = rtrim(ob_get_clean());

        ob_start();
        $command->run(array(),
                      array('omeka_version', '0.0.0'),
                      $this->application);
        $output = ob_get_clean();
        $this->assertNotEmpty($output, "\n");

        ob_start();
        $command->run(array(),
                      array('omeka_version', $oldVal,),
                      $this->application);
        ob_end_clean();
    }
}
