<?php

use PHPUnit\Framework\TestCase;

/**
 * @covers Nothing
 */
abstract class AbstractTest extends TestCase
{
    protected $application;

    final protected function setUp()
    {
        if (class_exists('Zend_Registry'))
            $this->application = Zend_Registry::get('omeka-cli-application');
        else
            $this->markTestSkipped('Error: Zend_Registry not set.\n');
    }
}
