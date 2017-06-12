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

    protected function setUp()
    {
        $omeka_path = getenv('OMEKA_PATH');
        if (!getenv('OMEKA_PATH'))
            $this->markTestSkipped('Error: OMEKA_PATH environment variable not defined.\n');

        $options = array(
            'omeka-path' => $omeka_path,
        );
        $this->application = new Application($options, array());
        $this->application->initialize();
    }

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
