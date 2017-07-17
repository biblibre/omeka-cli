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
        $command->run(array('quick' => true), array('dl', 'Coins'), $this->application);
        $output = ob_get_clean();

        $this->assertFileExists(PLUGIN_DIR . '/Coins');
        $this->assertFileIsReadable(PLUGIN_DIR . '/Coins/plugin.ini');
        $this->assertFileIsReadable(PLUGIN_DIR . '/Coins/CoinsPlugin.php');
        shell_exec('rm -rf ' . PLUGIN_DIR . '/Coins');
    }

//    public function testCanUpdatePlugins()
//    {
//        $command = new PluginCommand();
//
//        ob_start();
//        $command->run(array('quick' => true), array('up'), $this->application);
//        $output = ob_get_clean();
//
//        $this->assertRegExp('
///\AUpdating\.\.\.
//(.+)*\Z/', $output);
//    }
}
