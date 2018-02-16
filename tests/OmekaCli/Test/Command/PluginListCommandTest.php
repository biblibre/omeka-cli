<?php

namespace OmekaCli\Test\Command;

class PluginListCommandTest extends TestCase
{
    protected $commandName = 'plugin-list';

    public function testPluginListCommand()
    {
        $this->commandTester->execute(array());
        $output = $this->commandTester->getDisplay();

        $this->assertRegExp('/Coins\s+\|\s+\d+(\.\d+)*\s+\|\s+uninstalled/', $output);
        $this->assertRegExp('/ExhibitBuilder\s+\|\s+\d+(\.\d+)*\s+\|\s+uninstalled/', $output);
        $this->assertRegExp('/Foo\s+\|\s+0\.1\.0\s+\|\s+uninstalled/', $output);
        $this->assertRegExp('/SimplePages\s+\|\s+\d+(\.\d+)*\s+\|\s+uninstalled/', $output);
    }
}
