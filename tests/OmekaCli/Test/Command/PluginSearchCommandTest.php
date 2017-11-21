<?php

namespace OmekaCli\Test\Command;

class PluginSearchCommandTest extends TestCase
{
    protected $commandName = 'plugin-search';

    public function testPluginSearch()
    {
        $query = 'zotero';
        $status = $this->commandTester->execute(array('--exclude-github' => true, 'query' => $query));

        $this->assertEquals(0, $status);
        $this->assertRegExp('/ZoteroImport/', $this->commandTester->getDisplay());
    }
}
