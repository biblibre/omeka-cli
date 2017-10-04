<?php

namespace OmekaCli\Test\Command;

use OmekaCli\Application;
use OmekaCli\Sandbox\OmekaSandbox;
use OmekaCli\Context\Context;

class UpgradeCommandTest extends TestCase
{
    public function testUpgradeNeedsGitRepo()
    {
        $status = $this->command->run(array(), array());

        $this->assertEquals(1, $status);
        $this->assertRegExp('/needs a git repo to upgrade/', $this->logger->getOutput());
    }

    /**
     * @group slow
     */
    public function testUpgrade()
    {
        if (version_compare(PHP_VERSION, '7.1') >= 0) {
            $this->markTestSkipped('Only latest version of Omeka is compatible with PHP 7.1');
        }

        $options = array(
            'db-host' => getenv('OMEKA_DB_HOST'),
            'db-user' => getenv('OMEKA_DB_USER'),
            'db-pass' => getenv('OMEKA_DB_PASS'),
            'db-name' => getenv('OMEKA_DB_NAME'),
            'db-prefix' => 'upgradetest_',
            'omeka-site-title' => 'UpgradeCommand test',
            'version' => 'v2.4',
        );
        $tempdir = rtrim(`mktemp -d --tmpdir omeka-upgrade-test.XXXXXX`);
        $installCommand = $this->getCommand('install');
        $installCommand->run($options, array($tempdir));

        $sandbox = new OmekaSandbox();
        $sandbox->setContext(new Context($tempdir));
        $version = $sandbox->execute(function () {
            return get_option('omeka_version');
        });
        $this->assertEquals('2.4', $version);

        $application = new Application(array('omeka-path' => $tempdir), array());
        $application->initialize();
        $command = $application->getCommandManager()->getCommand('upgrade');
        $command->setLogger($this->logger);
        $status = $command->run(array(), array());

        $this->assertEquals(0, $status);

        $sandbox = new OmekaSandbox();
        $sandbox->setContext(new Context($tempdir));
        $version = $sandbox->execute(function () {
            return get_option('omeka_version');
        });
        $this->assertEquals('2.5.1', $version);

        unset($sandbox);
        rrmdir($tempdir);
    }

    protected function getCommandName()
    {
        return 'upgrade';
    }
}
