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
        $command->run(array(), array('Wrong arg'), $this->application);
        $output = ob_get_clean();

        $this->assertNotEmpty($output);
        $this->assertRegExp('
/\AUsage:
\tupgrade

.+

((.+)\n)*\z/', $output);
    }
}
