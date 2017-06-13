<?php

use PHPUnit\Framework\TestCase;

/**
 * @covers Nothing
 */
class TestsTemplate extends TestCase
{
    final protected function setUp()
    {
        if (class_exists('Zend_Registry'))
            $this->application = Zend_Registry::get('omeka-cli-application');
        else
            $this->markTestSkipped('Error: Zend_Registry not set.\n');
    }
}
