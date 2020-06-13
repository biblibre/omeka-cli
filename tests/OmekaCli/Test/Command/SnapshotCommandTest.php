<?php

namespace OmekaCli\Test\Command;

class SnapshotCommandTest extends TestCase
{
    protected $commandName = 'snapshot';

    public function testSnapshot()
    {
        $status = $this->commandTester->execute([]);

        $this->assertEquals(0, $status);

        $output = $this->commandTester->getDisplay();
        $this->assertRegExp('/Snapshot created at (.*)/', $output);

        preg_match('/Snapshot created at (.*)/', $output, $matches);
        $snapshot = $matches[1];
        $this->assertFileExists($snapshot);
    }
}
