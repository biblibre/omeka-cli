<?php

namespace OmekaCli;

use OmekaCli\Command\Proxy;
use OmekaCli\Console\Helper\ContextHelper;
use OmekaCli\Context\Context;
use OmekaCli\Sandbox\OmekaSandbox;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Application extends \Symfony\Component\Console\Application
{
    const EVENT_ID = 'OmekaCli';
    const COMMANDS_EVENT_NAME = 'commands';

    protected $omekaPath;

    public function __construct()
    {
        parent::__construct('omeka-cli', OMEKACLI_VERSION);
    }

    public function doRun(InputInterface $input, OutputInterface $output)
    {
        $input->setStream(STDIN);

        if ($omekaPath = $input->getParameterOption(array('--omeka-path', '-C'))) {
            if (!$this->isOmekaDir($omekaPath)) {
                throw new \Exception("$omekaPath is not an Omeka directory");
            }
        } else {
            $omekaPath = $this->searchOmekaDir();
        }
        $this->getHelperSet()->get('context')->setContext(new Context($omekaPath));

        $this->addPluginCommands();

        return parent::doRun($input, $output);
    }

    protected function getDefaultCommands()
    {
        $commands = array_merge(parent::getDefaultCommands(), array(
            new \OmekaCli\Command\InfoCommand(),
            new \OmekaCli\Command\InstallCommand(),
            new \OmekaCli\Command\CheckUpdatesCommand(),
            new \OmekaCli\Command\OptionsCommand(),
            new \OmekaCli\Command\SnapshotCommand(),
            new \OmekaCli\Command\SnapshotRestoreCommand(),
            new \OmekaCli\Command\UpgradeCommand(),
            new \OmekaCli\Command\Plugin\SearchCommand(),
            new \OmekaCli\Command\Plugin\DownloadCommand(),
            new \OmekaCli\Command\Plugin\EnableCommand(),
            new \OmekaCli\Command\Plugin\DisableCommand(),
            new \OmekaCli\Command\Plugin\UninstallCommand(),
            new \OmekaCli\Command\Plugin\UpdateCommand(),
        ));

        return $commands;
    }

    protected function getDefaultInputDefinition()
    {
        $definition = parent::getDefaultInputDefinition();
        $definition->addOption(new InputOption('omeka-path', 'C', InputOption::VALUE_REQUIRED, 'Tells where Omeka is installed. If omitted, Omeka will be searched in current directory and parent directories'));

        return $definition;
    }

    protected function getDefaultHelperSet()
    {
        $helperSet = parent::getDefaultHelperSet();
        $helperSet->set(new ContextHelper());

        return $helperSet;
    }

    protected function isOmekaDir($dir)
    {
        $sandbox = new OmekaSandbox();
        $sandbox->setContext(new Context($dir));
        $result = $sandbox->execute(function () {
            if (defined('OMEKA_VERSION')) {
                return true;
            }

            return false;
        }, OmekaSandbox::ENV_SHORTLIVED);

        return $result;
    }

    protected function searchOmekaDir()
    {
        $dir = realpath('.');
        while ($dir !== false && $dir !== '/' && !$this->isOmekaDir($dir)) {
            $dir = realpath($dir . '/..');
        }

        if ($dir !== false && $dir !== '/') {
            return $dir;
        }
    }

    protected function addPluginCommands()
    {
        $sandbox = $this->getSandbox();
        $eventId = self::EVENT_ID;
        $eventName = self::COMMANDS_EVENT_NAME;
        $commands = $sandbox->execute(function () use ($eventId, $eventName) {
            $items = array();

            if (class_exists('Zend_EventManager_StaticEventManager')) {
                $events = \Zend_EventManager_StaticEventManager::getInstance();
                $listeners = $events->getListeners($eventId, $eventName);

                if (false !== $listeners) {
                    foreach ($listeners as $listener) {
                        $items = array_merge($items, $listener->call());
                    }
                }
            }

            return $items;
        });

        $context = $this->getHelperSet()->get('context')->getContext();
        foreach ($commands as $class) {
            $command = new Proxy(null, $class, $context);
            $this->add($command);
        }
    }

    protected function getSandbox()
    {
        return $this->getHelperSet()->get('context')->getSandbox();
    }
}
