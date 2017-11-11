<?php

namespace OmekaCli\Plugin\Repository;

use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractRepository implements RepositoryInterface
{
    protected $output;

    public function __construct()
    {
        $this->output = new NullOutput();
    }

    public function setOutput(OutputInterface $output)
    {
        $this->output = $output;
    }
}
