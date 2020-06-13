<?php

namespace OmekaCli\Test\Command;

class CheckUpdatesCommandTest extends TestCase
{
    protected $commandName = 'check-updates';

    protected function setUp(): void
    {
        parent::setUp();

        $zip = new \ZipArchive();
        $zip->open(__DIR__ . '/../../../plugins/Dublin-Core-Extended-2.0.zip');
        $zip->extractTo(getenv('OMEKA_PATH') . '/plugins/');
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

    /**
     * @group slow
     */
    public function testIsOutputFormatOk()
    {
        $status = $this->commandTester->execute(array());

        $this->assertEquals(0, $status);
        $this->assertRegExp('/DublinCoreExtended \(2\.0 -> 2\.2\)/', $this->commandTester->getDisplay());
    }
}
