<?php

namespace OmekaCli\Test\Command;

use OmekaCli\Context\Context;
use OmekaCli\Sandbox\OmekaSandbox;
use Symfony\Component\Console\Tester\CommandTester;

class UpgradeCommandTest extends TestCase
{
    protected $commandName = 'upgrade';

    public function testUpgradeNeedsGitRepo()
    {
        $status = $this->commandTester->execute([]);

        $this->assertEquals(1, $status);
        $output = $this->commandTester->getDisplay();
        $this->assertRegExp('/needs a git repo to upgrade/', $output);
    }

    /**
     * @group slow
     */
    public function testUpgrade()
    {
        $current_version = '2.6';

        if (version_compare(PHP_VERSION, '7.3') >= 0) {
            $current_version = '2.7';
        }

        $branch = "v$current_version";

        $tempdir = rtrim(`mktemp -d --tmpdir omeka-upgrade-test.XXXXXX`);
        $input = [
            'omeka-path' => $tempdir,
            '--db-host' => getenv('OMEKA_DB_HOST'),
            '--db-user' => getenv('OMEKA_DB_USER'),
            '--db-pass' => getenv('OMEKA_DB_PASS'),
            '--db-name' => getenv('OMEKA_DB_NAME'),
            '--db-prefix' => 'upgradetest_',
            '--omeka-site-title' => 'UpgradeCommand test',
            '--branch' => $branch,
        ];
        $installCommand = $this->getCommand('install');
        $installCommandTester = new CommandTester($installCommand);
        $installCommandTester->execute($input);

        $sandbox = new OmekaSandbox();
        $sandbox->setContext(new Context($tempdir));
        $version = $sandbox->execute(function () {
            return get_option('omeka_version');
        }, OmekaSandbox::ENV_SHORTLIVED);
        $this->assertEquals($current_version, $version);

        $command = $this->getCommand('upgrade');
        $command->getHelper('context')->setContext(new Context($tempdir));
        $commandTester = new CommandTester($command);
        $status = $commandTester->execute([]);

        $this->assertEquals(0, $status);

        $sandbox = new OmekaSandbox();
        $sandbox->setContext(new Context($tempdir));
        $version = $sandbox->execute(function () {
            return get_option('omeka_version');
        }, OmekaSandbox::ENV_SHORTLIVED);

        $latest_version = rtrim(file_get_contents('http://api.omeka.org/latest-version'));
        $this->assertEquals($latest_version, $version);

        unset($sandbox);
        rrmdir($tempdir);
    }
}
