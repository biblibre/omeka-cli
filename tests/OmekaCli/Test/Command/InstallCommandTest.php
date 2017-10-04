<?php

namespace OmekaCli\Test\Command;

use OmekaCli\Sandbox\OmekaSandbox;
use OmekaCli\Context\Context;

class InstallCommandTest extends TestCase
{
    /**
     * @group slow
     */
    public function testBasicInstall()
    {
        $options = array(
            'db-host' => getenv('OMEKA_DB_HOST'),
            'db-user' => getenv('OMEKA_DB_USER'),
            'db-pass' => getenv('OMEKA_DB_PASS'),
            'db-name' => getenv('OMEKA_DB_NAME'),
            'db-prefix' => 'installtest_',
            'omeka-site-title' => 'InstallCommand test',
        );
        $tempdir = rtrim(`mktemp -d --tmpdir omeka-install-test.XXXXXX`);
        $this->command->run($options, array($tempdir));

        $sandbox = new OmekaSandbox();
        $sandbox->setContext(new Context($tempdir));
        $siteTitle = $sandbox->execute(function () {
            return get_option('site_title');
        });

        $this->assertEquals('InstallCommand test', $siteTitle);

        unset($sandbox);
        rrmdir($tempdir);
    }

    protected function getCommandName()
    {
        return 'install';
    }
}
