<?php

namespace OmekaCli\Command;

use OmekaCli\Application;

interface CommandInterface
{
    public function getOptionsSpec();
    public function getDescription();
    public function getUsage();

    public function run($options, $arguments, Application $application);
}
