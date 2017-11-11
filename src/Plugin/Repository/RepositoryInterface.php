<?php

namespace OmekaCli\Plugin\Repository;

use Symfony\Component\Console\Output\OutputInterface;

interface RepositoryInterface
{
    public function setOutput(OutputInterface $output);

    public function getDisplayName();

    public function search($query);

    public function find($id);

    public function download($id);
}
