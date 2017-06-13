<?php

use OmekaCli\Application;
use OmekaCli\Command\InfoCommand;

use PHPUnit\Framework\TestCase;

require_once 'AbstractTest.php';

/**
 * @covers InfoCommand
 */
final class InfoCommandTest extends AbstractTest
{
    protected $application;

    public function testIsOutputFormatOk()
    {
        $command = new InfoCommand();

        ob_start();
        $command->run(array(), array(), $this->application);
        $output = ob_get_clean();

        $this->assertRegExp('
/\AOmeka base directory: *(\/\w*)+
Omeka version: *.+
Database version: *.+
Admin theme: *.+
Public theme: *.+
Plugins \(actives\):
((.+ - .+)*\n)*Plugins \(inactives\):
((.+ - .+)*\n)*\z/', $output);
    }
}
