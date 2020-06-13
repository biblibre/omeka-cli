<?php

namespace OmekaCli\Test\Command;

use OmekaCli\Context\Context;
use OmekaCli\Omeka;
use Symfony\Component\Console\Tester\CommandTester;

class SnapshotRestoreCommandTest extends TestCase
{
    protected $commandName = 'snapshot-restore';

    public function testSnapshotRestore()
    {
        $snapshotCommand = $this->getCommand('snapshot');
        $snapshotCommandTester = new CommandTester($snapshotCommand);
        $snapshotCommandTester->execute([]);
        $output = $snapshotCommandTester->getDisplay();
        preg_match('/Snapshot created at (.*)/', $output, $matches);
        $snapshot = $matches[1];

        $originalSiteTitle = $this->getSandbox()->execute(function () {
            $siteTitle = get_option('site_title');
            set_option('site_title', 'Changed!');

            return $siteTitle;
        });

        $tempdir = rtrim(`mktemp -d --tmpdir omeka-snapshot-test.XXXXXX`);
        $this->commandTester->execute(['snapshot' => $snapshot, 'target' => $tempdir]);

        $omeka = new Omeka();
        $omeka->setContext(new Context($tempdir));
        $siteTitle = $omeka->get_option('site_title');
        $this->assertEquals($originalSiteTitle, $siteTitle);
    }
}
