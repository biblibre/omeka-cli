<?php

use OmekaCli\Command\PluginCommand;

use PHPUnit\Framework\TestCase;

require_once 'AbstractTest.php';

/**
 * @covers PluginCommand
 */
final class PluginCommandTest extends AbstractTest
{
    public function testShowUsageWhenRunWithoutArgument()
    {
        $command = new PluginCommand();

        ob_start();
        $command->run(array(), array(), $this->application);
        $output = ob_get_clean();

        $this->assertNotEmpty($output);
        $this->assertRegExp('
/\AUsage:
\tplugin.+

.+

(.+\n)*\z/', $output);
    }

    public function testShowUsageWhenRunWithWrongArgument()
    {
        $command = new PluginCommand();

        // Wrong subcommand
        ob_start();
        $command->run(array(), array(''), $this->application);
        $output = ob_get_clean();

        $this->assertNotEmpty($output);
        $this->assertRegExp('
/\AError: .+
Usage:
\tplugin.+

.+

(.+\n)*\z/', $output);

        // Right command, wrong argument
        ob_start();
        $command->run(array(), array('dl'), $this->application);
        $output = ob_get_clean();

        $this->assertNotEmpty($output);
        $this->assertRegExp('
/\AError: .+
Usage:
\tplugin.+

.+

(.+\n)*\z/', $output);

        // Right command, empty argument
        ob_start();
        $command->run(array(), array('dl', ''), $this->application);
        $output = ob_get_clean();

        $this->assertNotEmpty($output);
        $this->assertRegExp('
/\AError: .+
Usage:
\tplugin.+

.+

(.+\n)*\z/', $output);
    }

    public function testCanDownloadPlugins()
    {
        $command = new PluginCommand();

        ob_start();
        shell_exec('rm -rf ' . getenv('OMEKA_PATH') . '/plugins/BagIt');
        $command->run(array('quick' => true), array('dl', 'BagIt'), $this->application);
        $output = ob_get_clean();

        $this->assertNull(null);
        $this->assertFileExists(getenv('OMEKA_PATH') . '/plugins/BagIt');
        $this->assertFileIsReadable(getenv('OMEKA_PATH') . '/plugins/BagIt/plugin.ini');
        $this->assertFileIsReadable(getenv('OMEKA_PATH') . '/plugins/BagIt/BagItPlugin.php');
        shell_exec('rm -rf ' . getenv('OMEKA_PATH') . '/plugins/BagIt');
    }

    public function testShowPluginsToUpdate()
    {
        $command = new PluginCommand();

        ob_start();
        $command->run(array(), array('up'), $this->application);
        $output = ob_get_clean();

        $this->assertRegExp('/(.+\n)*/', $output);
    }
}
