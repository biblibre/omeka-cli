<?php

namespace OmekaCli\Plugin\Repository;

class GithubOmekaRepository extends GithubRepository
{
    protected function getOwner()
    {
        return 'omeka';
    }

    protected function getPossibleRepoNames($pluginName)
    {
        return array(
            "plugin-$pluginName",
            "$pluginName",
        );
    }
}
