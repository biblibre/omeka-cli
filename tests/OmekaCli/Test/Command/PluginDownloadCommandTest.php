<?php

namespace OmekaCli\Test\Command;

class PluginDownloadCommandTest extends TestCase
{
    protected $commandName = 'plugin-download';

    public function testPluginDownload()
    {
        $pluginName = 'CollectionTree';
        $pluginFile = getenv('OMEKA_PATH') . "/plugins/$pluginName/{$pluginName}Plugin.php";
        $this->assertFileNotExists($pluginFile);
        $status = $this->commandTester->execute(array('plugin-id' => $pluginName));

        $this->assertEquals(0, $status);
        $this->assertFileExists($pluginFile);
    }
}
