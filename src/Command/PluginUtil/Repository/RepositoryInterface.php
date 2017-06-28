<?php

namespace OmekaCli\Command\PluginUtil\Repository;

interface RepositoryInterface
{
    public function getDisplayName();

    public function find($pluginName);

    public function download($pluginName, $destDir);
}
