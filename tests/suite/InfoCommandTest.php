<?php

use OmekaCli\Application;
use OmekaCli\Command\InfoCommand;

use PHPUnit\Framework\TestCase;

/**
 * @covers InfoCommand
 */
final class InfoCommandTest extends TestCase
{
    protected $application;

    public function setUp()
    {
        $options = array(
            'omeka-path' => '/srv/http/Omeka',
        );
        $application = new Application($options, array());
        $application->initialize();

        $this->application = $application;
    }

    public function testIsOutputFormatOk()
    {
        $command = new InfoCommand();

        ob_start();
        $command->run(array(), array(), $this->application);
        $output = ob_get_clean();

        // TODO: make it beautiful...
        $this->assertRegExp('
/\AOmeka base directory: *(\/\w*)+
Omeka version: *[0-9]([\.-][0-9])*[\.-][0-9]
Database version: *[0-9]([\.-][0-9][\.-])*[0-9]
Admin theme: *(\w*[\.-]*)+
Public theme: *(\w*[\.-]*)+
Plugins \(actives\):
(((\w*[\.-]*)+ - [0-9]([\.-][0-9])*[\.-][0-9])*\n)*Plugins \(inactives\):
(((\w*[\.-]*)+ - [0-9]([\.-][0-9])*[\.-][0-9])*\n)*\z/', $output);
    }
}
