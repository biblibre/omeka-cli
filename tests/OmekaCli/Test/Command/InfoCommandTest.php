<?php

namespace OmekaCli\Test\Command;

class InfoCommandTest extends TestCase
{
    protected $commandName = 'info';

    public function testIsOutputFormatOk()
    {
        $this->commandTester->execute(array());

        $regex = "\A";
        $regex .= "Omeka base directory: +.+\n";
        $regex .= "Omeka version: +.+\n";
        $regex .= "Database version: +.+\n";
        $regex .= "Admin theme: +.+\n";
        $regex .= "Public theme: +.+\n";
        $regex .= "Plugins \(actives\):(\n\t.+)*\n";
        $regex .= "Plugins \(inactives\):(\n\t.+)*\n";
        $regex .= "\Z";
        $this->assertRegExp("/$regex/", $this->commandTester->getDisplay());
    }
}
