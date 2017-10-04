<?php

namespace OmekaCli\Test\Command;

class InfoCommandTest extends TestCase
{
    protected $command;

    public function testIsOutputFormatOk()
    {
        ob_start();
        $this->command->run(array(), array());
        $output = ob_get_clean();

        $regex = "\A";
        $regex .= "omeka-cli: +.+\n";
        $regex .= "Omeka base directory: +.+\n";
        $regex .= "Omeka version: +.+\n";
        $regex .= "Database version: +.+(\nWarning: Omeka version and database version are not the same!)?\n";
        $regex .= "Admin theme: +.+\n";
        $regex .= "Public theme: +.+\n";
        $regex .= "Plugins \(actives\):(\n\t.+)*\n";
        $regex .= "Plugins \(inactives\):(\n\t.+)*\n";
        $regex .= "\Z";
        $this->assertRegExp("/$regex/", $output);
    }

    protected function getCommandName()
    {
        return 'info';
    }
}
