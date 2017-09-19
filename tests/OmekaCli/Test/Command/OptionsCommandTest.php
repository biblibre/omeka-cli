<?php

namespace OmekaCli\Test\Command;

use OmekaCli\Test\TestCase;

class OptionsCommandTest extends TestCase
{
    public function testShowAllOptionsWhenRunWithoutArgument()
    {
        $command = $this->getCommand('options');

        ob_start();
        $command->run(array(), array(), $this->application);
        $output = ob_get_clean();

        $this->assertRegExp('/\A(.+=.*\n)*\Z/', $output);
    }

    public function testCanRetrieveExistingTableEntries()
    {
        $command = $this->getCommand('options');

        ob_start();
        $command->run(array(), array('omeka_version'), $this->application);
        $output = ob_get_clean();
        $this->assertRegexp('/\A[0-9a-zA-Z]+([\.-][0-9a-zA-Z]+)*\n\z/', $output);
    }

    public function testShowErrorOnNonExistingTableEntries()
    {
        $command = $this->getCommand('options');

        $retCode = $command->run(array(),
                      array('NonExistingTableEntries'),
                      $this->application);

        $this->assertEquals(1, $retCode);
        $this->assertRegExp('/\AError: option not found\Z/', $this->logger->getOutput());
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
