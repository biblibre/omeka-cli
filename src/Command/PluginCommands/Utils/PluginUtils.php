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

        return empty($plugins) ? null : array_pop($plugins);
    }

    public static function getIniInfos($pluginName)
    {
        return parse_ini_file(PLUGIN_DIR . '/' . $pluginName . '/plugin.ini');
    }

    public static function getMissingDependencies($pluginName)
    {
        $missingDeps = array();
        $ini = self::getIniInfos($pluginName);
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

    public static function getInstaller($plugin)
    {
        $broker = $plugin->getPluginBroker();
        $loader = new \Omeka_Plugin_Loader($broker,
                                           new \Omeka_Plugin_Ini(PLUGIN_DIR),
                                           new \Omeka_Plugin_Mvc(PLUGIN_DIR),
                                           PLUGIN_DIR);

        return new \Omeka_Plugin_Installer($broker, $loader);
    }
}
