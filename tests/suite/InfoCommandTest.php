<?php

use OmekaCli\Command\InfoCommand;

use PHPUnit\Framework\TestCase;

require_once 'AbstractTest.php';

/**
 * @covers InfoCommand
 */
final class InfoCommandTest extends AbstractTest
{
    public function testIsOutputFormatOk()
    {
        $command = $this->getCommand('info');

        ob_start();
        $command->run(array(), array(), $this->application);
        $output = ob_get_clean();

        $this->assertRegExp(
'/\Aomeka-cli: +.+
Omeka base directory: +.+
Omeka version: +.+
Database version: +.+(\nWarning: Omeka version and database version are not the same!)?
Admin theme: +.+
Public theme: +.+
Plugins \(actives\):(\n.+)*
Plugins \(inactives\):
(.+\n)*\Z/', $output);
    }
}
