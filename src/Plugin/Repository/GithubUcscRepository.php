<?php

namespace OmekaCli\Plugin\Repository;

class GithubUcscRepository extends GithubRepository
{
    protected function getOwner()
    {
        return 'UCSCLibrary';
    }

    protected function getPossibleRepoNames($pluginName)
    {
        return array(
            "$pluginName",
            "plugin-$pluginName",
            "Omeka-$pluginName",
        );
    }
}
