<?php

use OmekaCli\Application;
use OmekaCli\Command\InfoCommand;

use PHPUnit\Framework\TestCase;

/**
 * @covers InfoCommand
 */
final class InfoCommandTest extends TestCase
{
    public function testIsOutputFormatOk()
    {
        $omeka_path = getenv('OMEKA_PATH');
        if (!getenv('OMEKA_PATH'))
            exit('Error: OMEKA_PATH environment variable not defined.\n');

        $options = array(
            'omeka-path' => $omeka_path,
        );
        $application = new Application($options, array());
        $application->initialize();

        $command = new InfoCommand();

        ob_start();
        $command->run(array(), array(), $application);
        $output = ob_get_clean();

        // TODO: make it beautiful...
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
