<?php

namespace OmekaCli\Test;

use OmekaCli\Application;
use OmekaCli\Test\Tester\ApplicationTester;

class PluginsCommandsTest extends TestCase
{
    protected $applicationTester;

    public function setUp()
    {
        parent::setUp();

        $this->installPlugin('Foo');
        $this->flushSandboxes();

        $app = new Application();
        $app->setAutoExit(false);
        $this->applicationTester = new ApplicationTester($app);
    }

    public function tearDown()
    {
        $this->uninstallPlugin('Foo');

        parent::tearDown();
    }

    public function testPluginsCommandsAreListed()
    {
        $input = array(
            '--omeka-path' => getenv('OMEKA_PATH'),
            'command' => 'list',
        );
        $this->applicationTester->run($input);

        $this->assertContains('foo:bar', $this->applicationTester->getDisplay());
    }

    public function testPluginsCommandsCanBeExecuted()
    {
        $input = array(
            '--omeka-path' => getenv('OMEKA_PATH'),
            'command' => 'foo:bar',
        );
        $this->applicationTester->run($input);

        $this->assertContains('Hello, omeka-cli!', $this->applicationTester->getDisplay());
    }
}
