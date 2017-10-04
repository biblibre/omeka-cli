<?php

namespace OmekaCli\Test\Command;

class SnapshotCommandTest extends TestCase
{
    public function testSnapshot()
    {
        $status = $this->command->run(array(), array());

        $this->assertEquals(0, $status);

        $output = $this->logger->getOutput();
        $this->assertRegExp('/Snapshot created at (.*)/', $output);

        preg_match('/Snapshot created at (.*)/', $output, $matches);
        $snapshot = $matches[1];
        $this->assertFileExists($snapshot);
    }

    protected function getCommandName()
    {
        return 'snapshot';
    }
}
