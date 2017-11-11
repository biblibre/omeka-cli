<?php

namespace OmekaCli\Test\Command;

use OmekaCli\Context\Context;
use OmekaCli\Sandbox\OmekaSandbox;

class InstallCommandTest extends TestCase
{
    protected $commandName = 'install';

    /**
     * @group slow
     */
    public function testBasicInstall()
    {
        $tempdir = rtrim(`mktemp -d --tmpdir omeka-install-test.XXXXXX`);
        $input = array(
            'omeka-path' => $tempdir,
            '--db-host' => getenv('OMEKA_DB_HOST'),
            '--db-user' => getenv('OMEKA_DB_USER'),
            '--db-pass' => getenv('OMEKA_DB_PASS'),
            '--db-name' => getenv('OMEKA_DB_NAME'),
            '--db-prefix' => 'installtest_',
            '--omeka-site-title' => 'InstallCommand test',
        );
        $this->commandTester->execute($input);

        $sandbox = new OmekaSandbox();
        $sandbox->setContext(new Context($tempdir));
        $siteTitle = $sandbox->execute(function () {
            return get_option('site_title');
        }, OmekaSandbox::ENV_SHORTLIVED);

        $this->assertEquals('InstallCommand test', $siteTitle);

        unset($sandbox);
        rrmdir($tempdir);
    }
}
