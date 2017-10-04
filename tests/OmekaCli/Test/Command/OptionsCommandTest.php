<?php

namespace OmekaCli\Test\Command;

class OptionsCommandTest extends TestCase
{
    public function testShowAllOptionsWhenRunWithoutArgument()
    {
        ob_start();
        $this->command->run(array(), array());
        $output = ob_get_clean();

        $this->assertRegExp('/\A(.+=.*\n)*\Z/', $output);
    }

    public function testCanRetrieveExistingTableEntries()
    {
        ob_start();
        $this->command->run(array(), array('omeka_version'));
        $output = ob_get_clean();
        $this->assertRegexp('/\A[0-9a-zA-Z]+([\.-][0-9a-zA-Z]+)*\n\z/', $output);
    }

    public function testShowErrorOnNonExistingTableEntries()
    {
        $retCode = $this->command->run(array(), array('NonExistingOption'));

        $this->assertEquals(1, $retCode);
        $this->assertRegExp('/\AError: option not found\Z/', $this->logger->getOutput());
    }

    public function testCanEditExistingTableEntries()
    {
        ob_start();
        $this->command->run(array(), array('site_title', 'yee'));
        $output = ob_get_clean();

        $sandbox = $this->getSandbox();
        $siteTitle = $sandbox->execute(function () {
            return get_option('site_title');
        });
        $this->assertEquals('yee', $siteTitle);
    }

    protected function getCommandName()
    {
        return 'options';
    }
}
