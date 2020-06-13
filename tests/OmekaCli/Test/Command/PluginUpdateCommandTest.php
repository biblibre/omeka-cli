<?php

namespace OmekaCli\Test\Command;

class PluginUpdateCommandTest extends TestCase
{
    protected $commandName = 'plugin-update';

    protected function setUp(): void
    {
        parent::setUp();

        $zip = new \ZipArchive();
        $zip->open(__DIR__ . '/../../../plugins/Dublin-Core-Extended-2.0.zip');
        $zip->extractTo(getenv('OMEKA_PATH') . '/plugins');
        $zip->close();

        $this->installPlugin('DublinCoreExtended');

        $this->flushSandboxes();
    }

    protected function tearDown(): void
    {
        $this->uninstallPlugin('DublinCoreExtended');
        rrmdir(getenv('OMEKA_PATH') . '/plugins/DublinCoreExtended');
        $this->flushSandboxes();

        parent::tearDown();
    }

    public function testPluginUpdate()
    {
        $dce_refines = $this->getNewSandbox()->execute(function () {
            return get_option('dublin_core_extended_refines');
        });
        $this->assertNull($dce_refines);

        $status = $this->commandTester->execute(['name' => 'DublinCoreExtended']);

        $this->assertEquals(0, $status);

        $version = $this->getNewSandbox()->execute(function () {
            $plugin = \Zend_Registry::get('plugin_loader')->getPlugin('DublinCoreExtended');

            return $plugin->getDbVersion();
        });

        $this->assertEquals('2.2', $version);
        $dce_refines = $this->getNewSandbox()->execute(function () {
            return get_option('dublin_core_extended_refines');
        });
        $this->assertNotNull($dce_refines);
    }
}
