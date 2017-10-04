<?php

namespace OmekaCli\Test\Command\Plugin;

use OmekaCli\Test\Command\TestCase;

class PluginInstallCommandTest extends TestCase
{
    public function testPluginInstall()
    {
        $isActive = $this->getSandbox()->execute(function () {
            return plugin_is_active('SimplePages');
        });
        $this->assertEquals(false, $isActive);

        $this->command->run(array(), array('SimplePages'));

        $isActive = $this->getSandbox()->execute(function () {
            return plugin_is_active('SimplePages');
        });
        $this->assertEquals(true, $isActive);

        $pagesCount = $this->getSandbox()->execute(function () {
            return count(get_db()->getTable('SimplePagesPage')->findAll());
        });
        $this->assertEquals(1, $pagesCount);
    }

    protected function getCommandName()
    {
        return 'plugin-install';
    }
}
