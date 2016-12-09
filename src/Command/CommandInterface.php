<?php

namespace OmekaCli\Command;

interface CommandInterface
{
    public function getOptionsSpec();
    public function getDescription();
    public function getUsage();

    public function run($options, $arguments, $application);
}
