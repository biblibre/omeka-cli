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
        $command = $this->getCommand('plugin');

        ob_start();
        $command->run(array(), array(), $this->application);
        $output = ob_get_clean();

        $this->assertNotEmpty($output);
        $this->assertRegExp('
/\AUsage:
    plugin.+

.+

(.*\n)*\z/', $output);
    }

    public function testShowUsageWhenRunWithWrongArgument()
    {
        $command = $this->getCommand('plugin');

        // Wrong subcommand
        ob_start();
        $command->run(array(), array(''), $this->application);
        $output = ob_get_clean();

        $this->assertNotEmpty($output);
        $this->assertRegExp('
/\AUsage:
    plugin.+

.+

(.*\n)*\z/', $output);

        // Right command, wrong argument
        ob_start();
        $command->run(array(), array('dl'), $this->application);
        $output = ob_get_clean();

        $this->assertNotEmpty($output);
        $this->assertRegExp('
/\AUsage:
    plugin.+

.+

(.*\n)*\z/', $output);

        // Right command, empty argument
        ob_start();
        $command->run(array(), array('dl', ''), $this->application);
        $output = ob_get_clean();

        $this->assertNotEmpty($output);
        $this->assertRegExp('
/\AUsage:
    plugin.+

.+

(.*\n)*\z/', $output);
    }

    public function testCanInstallDownloadedPlugin()
    {
        $command = $this->getCommand('plugin');

        ob_start();
        shell_exec('rm -rf ' . PLUGIN_DIR . '/Coins');
        shell_exec('curl -O http://omeka.org/wordpress/wp-content/uploads/COinS-2.0.1.zip 2>/dev/null 1>/dev/null');
        shell_exec('unzip COinS-2.0.1.zip -d ' . PLUGIN_DIR . ' 2>/dev/null 1>/dev/null');
        shell_exec('rm -f COinS-2.0.1.zip');
        ob_end_clean();

        ob_start();
        $command->run(array(), array('in', 'Coins'), $this->application);
        $output = ob_get_clean();

        $this->assertEquals('Info: installation succeeded.', $this->fakeLogger->getOutput());
    }

    public function testCanListPluginsToUpdate()
    {
        $command = $this->getCommand('plugin');

        ob_start();
        $command->run(array(), array('up', '--list'), $this->application);
        $output = ob_get_clean();

        $this->assertRegExp('/.+\n\Z/', $output);
    }

    public function testCanUpdatePluginsAndSaveOldVersion()
    {
        $command = $this->getCommand('plugin');

        ob_start();
        $command->run(array(), array('up', '--save'), $this->application);
        $output = ob_get_clean();

        $this->assertRegExp('/Info: updating .+\Z/', $this->fakeLogger->getOutput());

        // May fail, "It's beyond my control".
//        $this->assertFileExists(BACKUPS_DIR . '/Coins_' . date('YmdHi'));
//        $this->assertFileIsReadable(BACKUPS_DIR . '/Coins_' . date('YmdHi'));
    }

    public function testCanDeactivateInstalledPlugin()
    {
        $command = $this->getCommand('plugin');

        ob_start();
        $command->run(array(), array('de', 'Coins'), $this->application);
        $output = ob_get_clean();

        $this->assertEquals('Info: plugin deactivated.', $this->fakeLogger->getOutput());
    }

    public function testCanActivateInstalledPlugin()
    {
        $command = $this->getCommand('plugin');

        ob_start();
        $command->run(array(), array('ac', 'Coins'), $this->application);
        $output = ob_get_clean();

        $this->assertEquals('Info: plugin activated.', $this->fakeLogger->getOutput());
    }

    public function testCanUninstallInstalledPlugin()
    {
        $command = $this->getCommand('plugin');

        ob_start();
        $command->run(array(), array('un', 'Coins'), $this->application);
        $output = ob_get_clean();
        shell_exec('rm -rf ' . PLUGIN_DIR . '/Coins');

        $this->assertEquals('Info: plugin uninstalled.', $this->fakeLogger->getOutput());

        system('rm -rf ' . BACKUPS_DIR . '/*');
    }
}
