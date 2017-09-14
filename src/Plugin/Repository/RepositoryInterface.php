<?php

namespace OmekaCli\Plugin\Repository;

interface RepositoryInterface
{
    public function getDisplayName();

    public function find($pluginName);

    public function download($pluginName, $destDir);
}
