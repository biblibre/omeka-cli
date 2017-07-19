<?php

use OmekaCli\Command\UpgradeCommand;

use PHPUnit\Framework\TestCase;

require_once 'AbstractTest.php';

/**
 * @covers UpgradeCommand
 */
final class UpgradeCommandTest extends AbstractTest
{
    public function testCanShowHelp()
    {
        $command = new UpgradeCommand();

        ob_start();
        $command->run(array('Wrong option' => true), array(), $this->application);
        $output = ob_get_clean();

        $this->assertNotEmpty($output);
        $this->assertRegExp('
/\AError: .+
Usage:
    upgrade

.+
\z/', $output);

        ob_start();
        $command->run(array(), array('Wrong', 'args'), $this->application);
        $output = ob_get_clean();

        $this->assertNotEmpty($output);
        $this->assertRegExp('
/\AError: .+
Usage:
    upgrade

.+
\z/', $output);

        ob_start();
        $command->run(array('Wrong option' => true), array('Wrong', 'args'), $this->application);
        $output = ob_get_clean();

        $this->assertNotEmpty($output);
        $this->assertRegExp('
/\AError: .+
Usage:
    upgrade

.+
\z/', $output);
    }
}
