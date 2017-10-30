<?php

namespace OmekaCli\Plugin\Repository;

use Psr\Log\LoggerAwareInterface;

interface RepositoryInterface extends LoggerAwareInterface
{
    public function getDisplayName();

    public function search($query);

    public function find($id);

    public function download($id);
}
