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

        $this->assertRegexp('/\A[0-9]+(\.[0-9]+)*\n\z/', $output);
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
        $command->run(array(), array('site_title', 'yee'), $this->application);
        $output = ob_get_clean();
        $this->assertEquals(get_option('site_title'), 'yee');
    }
}
