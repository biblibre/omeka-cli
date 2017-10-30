<?php

namespace OmekaCli\Test\Command\Plugin;

use OmekaCli\Test\Command\TestCase;

class PluginDownloadCommandTest extends TestCase
{
    public function testPluginDownload()
    {
        $pluginName = 'SolrSearch';
        $pluginFile = getenv('OMEKA_PATH') . "/plugins/$pluginName/{$pluginName}Plugin.php";
        $this->assertFileNotExists($pluginFile);
        $status = $this->command->run(array(), array($pluginName));

        $this->assertEquals(0, $status);
        $this->assertFileExists($pluginFile);
    }

    protected function getCommandName()
    {
        return 'plugin-download';
    }
}
