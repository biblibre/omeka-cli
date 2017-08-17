<?php

namespace OmekaCli\Command\PluginCommands\Utils;

use Omeka\Plugin;
use Omeka\Plugin\Broker;
use Omeka\Plugin\Installer;

class PluginUtils
{
    public static function getPlugin($pluginName)
    {
        $plugins = get_db()->getTable('Plugin')->findBy(array('name' => $pluginName));
        if (empty($plugins)) {
            $this->logger->error('plugin not installed');
            return 1;
        }

        return array_pop($plugins);
    }

    public static function getMissingDependencies($plugin)
    {
        $missingDeps = array();
        $ini = parse_ini_file(PLUGIN_DIR . '/' . $plugin->name . '/plugin.ini');
        if (isset($ini['required_plugins'])) {
            $deps = $ini['required_plugins'];
            $deps = explode(',', $deps);
            $deps = array_map("trim", $deps);
            $deps = array_filter($deps);
            foreach ($deps as $dep)
                if (!plugin_is_active($dep))
                    $missingDeps[] = $dep;
        }

        return $missingDeps;
    }
}
