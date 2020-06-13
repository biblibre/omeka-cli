<?php

namespace OmekaCli\Test;

use OmekaCli\Application;
use OmekaCli\Test\Tester\ApplicationTester;

class PluginsCommandsTest extends TestCase
{
    protected $applicationTester;

    protected function setUp(): void
    {
        parent::setUp();

        $this->installPlugin('Foo');
        $this->flushSandboxes();

        $app = new Application();
        $app->setAutoExit(false);
        $this->applicationTester = new ApplicationTester($app);
    }

    protected function tearDown(): void
    {
        $this->uninstallPlugin('Foo');

        parent::tearDown();
    }

    public function testPluginsCommandsAreListed()
    {
        $input = [
            '--omeka-path' => getenv('OMEKA_PATH'),
            'command' => 'list',
        ];
        $this->applicationTester->run($input);

        $this->assertStringContainsString('foo:bar', $this->applicationTester->getDisplay());
    }

    public function testPluginsCommandsCanBeExecuted()
    {
        $input = [
            '--omeka-path' => getenv('OMEKA_PATH'),
            'command' => 'foo:bar',
        ];
        $this->applicationTester->run($input);

        $this->assertStringContainsString('Hello, omeka-cli!', $this->applicationTester->getDisplay());
    }
}
