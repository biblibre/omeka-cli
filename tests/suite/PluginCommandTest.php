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

//    public function testCanDownloadPlugins()
//    {
//        $command = new PluginCommand();
//
//        ob_start();
//        $command->run(array('quick' => true), array('dl', 'Coins'), $this->application);
//        $output = ob_get_clean();
//
//        $this->assertFileExists(PLUGIN_DIR . '/Coins');
//        $this->assertFileIsReadable(PLUGIN_DIR . '/Coins/plugin.ini');
//        $this->assertFileIsReadable(PLUGIN_DIR . '/Coins/CoinsPlugin.php');
//        shell_exec('rm -rf ' . PLUGIN_DIR . '/Coins');
//    }

    public function testCanInstallDownloadedPlugin()
    {
        $command = new PluginCommand();

        ob_start();
        shell_exec('rm -rf ' . PLUGIN_DIR . '/Coins');
        shell_exec('curl -O http://omeka.org/wordpress/wp-content/uploads/COinS-2.0.1.zip 2>/dev/null 1>/dev/null');
        shell_exec('unzip COinS-2.0.1.zip -d ' . PLUGIN_DIR . ' 2>/dev/null 1>/dev/null');
        shell_exec('rm -f COinS-2.0.1.zip');
        ob_end_clean();

        ob_start();
        $command->run(array(), array('in', 'Coins'), $this->application);
        $output = ob_get_clean();

        $this->assertEquals('Installation succeeded.' . PHP_EOL, $output);
    }

    public function testCanUpdatePlugins()
    {
        $command = new PluginCommand();

        ob_start();
        $command->run(array('quick' => true), array('up'), $this->application);
        $output = ob_get_clean();

        $this->assertRegExp('
/\AUpdating\.\.\.
(.+)*\Z/', $output);
    }

    public function testCanUninstallInstalledPlugin()
    {
        $command = new PluginCommand();

        ob_start();
        $command->run(array(), array('un', 'Coins'), $this->application);
        $output = ob_get_clean();
        shell_exec('rm -rf ' . PLUGIN_DIR . '/Coins');

        $this->assertEquals('Plugin uninstalled.' . PHP_EOL, $output);
    }
}
