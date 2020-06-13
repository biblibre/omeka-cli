<?php

namespace OmekaCli\Test\Command;

class StatusCommandTest extends TestCase
{
    protected $commandName = 'status';

    protected function setUp(): void
    {
        parent::setUp();

        $this->flushSandboxes();
    }

    public function testIsOutputFormatOk()
    {
        $this->commandTester->execute([]);

        $output = $this->commandTester->getDisplay();
        $this->assertRegExp('|Omeka base directory:\s+' . preg_quote(getenv('OMEKA_PATH')) . '|', $output);
        $this->assertRegExp('/Omeka version:\s+\d+(\.\d+)*/', $output);
        $this->assertRegExp('/Database version:\s+\d+(\.\d+)*/', $output);
        $this->assertRegExp('/Admin theme:\s+default/', $output);
        $this->assertRegExp('/Public theme:\s+default/', $output);
        $this->assertRegExp('/Installed plugins:\s+0 \(0 active\)/', $output);
        $this->assertRegExp('/Uninstalled plugins:\s+4/', $output);
    }
}
