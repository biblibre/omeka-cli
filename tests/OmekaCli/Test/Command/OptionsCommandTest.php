<?php

namespace OmekaCli\Test\Command;

class OptionsCommandTest extends TestCase
{
    protected $commandName = 'options';

    public function testShowAllOptionsWhenRunWithoutArgument()
    {
        $this->commandTester->execute(array());

        $this->assertRegExp('/\A(.+=.*\n)*\Z/', $this->commandTester->getDisplay());
    }

    public function testCanRetrieveExistingTableEntries()
    {
        $this->commandTester->execute(array('name' => 'omeka_version'));

        $output = $this->commandTester->getDisplay();
        $this->assertRegexp('/\A[0-9a-zA-Z]+([\.-][0-9a-zA-Z]+)*\n\z/', $output);
    }

    public function testShowErrorOnNonExistingTableEntries()
    {
        $retCode = $this->commandTester->execute(array('name' => 'NonExistingOption'));

        $this->assertEquals(1, $retCode);
        $this->assertRegExp('/\AError: Option not found\Z/', $this->commandTester->getDisplay());
    }

    public function testCanEditExistingTableEntries()
    {
        $this->commandTester->execute(array('name' => 'site_title', 'value' => 'yee'));

        $sandbox = $this->getSandbox();
        $siteTitle = $sandbox->execute(function () {
            return get_option('site_title');
        });
        $this->assertEquals('yee', $siteTitle);
    }
}
