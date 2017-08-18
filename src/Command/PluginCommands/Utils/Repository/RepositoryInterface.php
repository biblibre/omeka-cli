<?php

namespace OmekaCli\Command\PluginCommands\Utils\Repository;

interface RepositoryInterface
{
    public function getDisplayName();

    public function find($pluginName);

    public function download($pluginName, $destDir);
}
