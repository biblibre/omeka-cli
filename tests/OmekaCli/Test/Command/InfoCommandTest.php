<?php

namespace OmekaCli\Test\Command;

use OmekaCli\Test\TestCase;

class InfoCommandTest extends TestCase
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
