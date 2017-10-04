<?php

namespace OmekaCli\Test\Command;

use OmekaCli\Sandbox\SandboxFactory;

class CheckUpdatesCommandTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        $url = 'http://omeka.org/wordpress/wp-content/uploads/Dublin-Core-Extended-2.0.zip';
        $tempfile = tempnam(sys_get_temp_dir(), 'DublinCoreExtended');
        file_put_contents($tempfile, fopen($url, 'r'));
        $zip = new \ZipArchive();
        $zip->open($tempfile);
        $zip->extractTo(getenv('OMEKA_PATH') . '/plugins/');
        $zip->close();

        $this->installPlugin('DublinCoreExtended');

        SandboxFactory::flush();
    }

    public function tearDown()
    {
        $this->uninstallPlugin('DublinCoreExtended');
    }

    /**
     * @group slow
     */
    public function testIsOutputFormatOk()
    {
        ob_start();
        $status = $this->command->run(array(), array());
        $output = ob_get_clean();

        $this->assertEquals(0, $status);
        $this->assertRegExp('/DublinCoreExtended \(2\.0 -> 2\.2\)/', $output);
    }

    protected function getCommandName()
    {
        return 'check-updates';
    }
}
