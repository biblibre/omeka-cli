<?php

namespace OmekaCli\Test\Command;

class PluginListCommandTest extends TestCase
{
    protected $commandName = 'plugin-list';

    public function testPluginListCommand()
    {
        $this->commandTester->execute(array());
        $output = $this->commandTester->getDisplay();

        $this->assertRegExp('/Coins\s+\|\s+2\.0\.3\s+\|\s+uninstalled/', $output);
        $this->assertRegExp('/ExhibitBuilder\s+\|\s+3\.3\.3\s+\|\s+uninstalled/', $output);
        $this->assertRegExp('/Foo\s+\|\s+0\.1\.0\s+\|\s+uninstalled/', $output);
        $this->assertRegExp('/SimplePages\s+\|\s+3\.0\.8\s+\|\s+uninstalled/', $output);
    }
}
