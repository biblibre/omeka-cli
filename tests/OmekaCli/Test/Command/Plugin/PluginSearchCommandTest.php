<?php

namespace OmekaCli\Test\Command\Plugin;

use OmekaCli\Test\Command\TestCase;

class PluginSearchCommandTest extends TestCase
{
    public function testPluginSearch()
    {
        $query = 'universal';
        ob_start();
        $status = $this->command->run(array('exclude-github' => true), array($query));
        $output = ob_get_clean();

        $this->assertEquals(0, $status);
        $this->assertRegExp('/^UniversalViewer/', $output);
    }

    protected function getCommandName()
    {
        return 'plugin-search';
    }
}
