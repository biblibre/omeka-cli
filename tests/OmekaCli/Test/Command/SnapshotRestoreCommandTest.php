<?php

namespace OmekaCli\Test\Command;

use OmekaCli\Omeka;
use OmekaCli\Context\Context;

class SnapshotRestoreCommandTest extends TestCase
{
    public function testSnapshotRestore()
    {
        $snapshotCommand = $this->getCommand('snapshot');
        $snapshotCommand->run(array(), array());
        $output = $this->logger->getOutput();
        preg_match('/Snapshot created at (.*)/', $output, $matches);
        $snapshot = $matches[1];

        $originalSiteTitle = $this->getSandbox()->execute(function () {
            $siteTitle = get_option('site_title');
            set_option('site_title', 'Changed!');

            return $siteTitle;
        });

        $tempdir = rtrim(`mktemp -d --tmpdir omeka-snapshot-test.XXXXXX`);
        $this->command->run(array(), array($snapshot, $tempdir));

        $omeka = new Omeka();
        $omeka->setContext(new Context($tempdir));
        $siteTitle = $omeka->get_option('site_title');
        $this->assertEquals($originalSiteTitle, $siteTitle);
    }

    protected function getCommandName()
    {
        return 'snapshot-restore';
    }
}
