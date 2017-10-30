<?php

namespace OmekaCli\Command;

use GetOptionKit\OptionParser;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Zend_EventManager_StaticEventManager;
use OmekaCli\Sandbox\SandboxFactory;
use OmekaCli\Context\Context;
use OmekaCli\Context\ContextAwareInterface;
use OmekaCli\Context\ContextAwareTrait;

class Manager implements ContextAwareInterface, LoggerAwareInterface
{
    use ContextAwareTrait;
    use LoggerAwareTrait;

    const EVENT_ID = 'OmekaCli';
    const COMMANDS_EVENT_NAME = 'commands';
    const COMMAND_INTERFACE = 'OmekaCli\Command\CommandInterface';

    protected $commands;
    protected $aliases;
    protected $commandAliases;

    public function __construct()
    {
        $this->setContext(new Context());
        $this->setLogger(new NullLogger());
    }

    public function getCommandsNames()
    {
        return array_keys($this->commands);
    }

    public function getCommand($name)
    {
        if (isset($this->commands[$name])) {
            return $this->commands[$name];
        }

        if (isset($this->aliases[$name])) {
            return $this->aliases[$name];
        }
    }

    public function getCommandAliases($commandName)
    {
        if (isset($this->commandAliases[$commandName])) {
            return $this->commandAliases[$commandName];
        }
    }

    public function run($commandName, $args = array())
    {
        $command = $this->getCommand($commandName);
        if (!$command) {
            throw new \Exception("Unknown command $commandName");
        }

        $optionsSpec = $command->getOptionsSpec();
        $parser = new OptionParser($command->getOptionsSpec());
        try {
            $result = $parser->parse($args);
        } catch (\Exception $e) {
            error_log($command->getUsage());

            return 1;
        }

        return $command->run($result->toArray(), $result->getArguments());
    }

    public function initialize()
    {
        $this->commands = array();

        $this->registerCommand('version', 'OmekaCli\Command\VersionCommand');
        $this->registerCommand('list', 'OmekaCli\Command\ListCommand');
        $this->registerCommand('help', 'OmekaCli\Command\HelpCommand');
        $this->registerCommand('info', 'OmekaCli\Command\InfoCommand');
        $this->registerCommand('install', 'OmekaCli\Command\InstallCommand');
        $this->registerCommand('upgrade', 'OmekaCli\Command\UpgradeCommand');
        $this->registerCommand('options', 'OmekaCli\Command\OptionsCommand');

        $this->registerCommand('plugin-activate', 'OmekaCli\Command\Plugin\ActivateCommand', array('plac'));
        $this->registerCommand('plugin-deactivate', 'OmekaCli\Command\Plugin\DeactivateCommand', array('plde'));
        $this->registerCommand('plugin-install', 'OmekaCli\Command\Plugin\InstallCommand', array('plin'));
        $this->registerCommand('plugin-uninstall', 'OmekaCli\Command\Plugin\UninstallCommand', array('plun'));
        $this->registerCommand('plugin-download', 'OmekaCli\Command\Plugin\DownloadCommand', array('pldl'));
        $this->registerCommand('plugin-update', 'OmekaCli\Command\Plugin\UpdateCommand', array('plup'));
        $this->registerCommand('check-updates', 'OmekaCli\Command\CheckUpdatesCommand', array('chup'));
        $this->registerCommand('snapshot', 'OmekaCli\Command\SnapshotCommand', array('snap'));
        $this->registerCommand('snapshot-restore', 'OmekaCli\Command\SnapshotRestoreCommand', array('restore'));

        $this->registerPluginCommands();
    }

    protected function registerPluginCommands()
    {
        $sandbox = $this->getSandbox();
        $commands = $sandbox->execute(function () {
            return $this->processEvent(self::COMMANDS_EVENT_NAME);
        });

        foreach ($commands as $name => $commandSpec) {
            if (is_array($commandSpec)) {
                $aliases = isset($commandSpec['aliases']) ? $commandSpec['aliases'] : array();
                $class = isset($commandSpec['class']) ? $commandSpec['class'] : null;
            } else {
                $class = $commandSpec;
            }

            $this->registerCommand($name, $class, $aliases, true);
        }
    }

    protected function processEvent($eventName)
    {
        $items = array();

        if (class_exists('Zend_EventManager_StaticEventManager')) {
            $events = Zend_EventManager_StaticEventManager::getInstance();
            $listeners = $events->getListeners(self::EVENT_ID, $eventName);

            if (false !== $listeners) {
                foreach ($listeners as $listener) {
                    $items = array_merge($items, $listener->call());
                }
            }
        }

        return $items;
    }

    /**
     * Register a new command.
     *
     * @param string   $name            Command name
     * @param string   $class           the class name of the command
     * @param string[] $aliases         aliases to this command
     * @param bool     $isPluginCommand Whether the command is defined in a
     *                                  plugin
     */
    protected function registerCommand($name, $class, $aliases = array(), $isPluginCommand = false)
    {
        if (isset($this->commands[$name])) {
            $this->logger->warning('Command {name} is already registered', array(
                'name' => $name,
            ));

            return;
        }

        if ($this->isCommandClass($class)) {
            $this->commands[$name] = new Proxy(array(
                'class' => $class,
                'is_plugin' => $isPluginCommand,
            ));
            $this->commands[$name]->setLogger($this->logger);
            $this->commands[$name]->setContext($this->getContext());
            $this->commands[$name]->setCommandManager($this);

            $this->registerAliases($aliases, $name);
        }
    }

    protected function isCommandClass($class)
    {
        if (!is_string($class)) {
            return false;
        }

        $interface = self::COMMAND_INTERFACE;

        if (class_exists($class) && in_array($interface, class_implements($class))) {
            return true;
        }

        $sandbox = $this->getSandbox();
        try {
            $result = $sandbox->execute(function () use ($class, $interface) {
                if (in_array($interface, class_implements($class))) {
                    return true;
                }

                return false;
            });
        } catch (\Exception $e) {
            $this->logger->error("Trying to load $class resulted in an error: {message}", array('message' => $e->getMessage()));

            return false;
        }

        if (!$result) {
            $this->logger->error("Class $class does not implement {interface}", array('interface' => self::COMMAND_INTERFACE));

            return false;
        }

        return true;
    }

    protected function registerAliases($names, $commandName)
    {
        foreach ($names as $name) {
            $this->registerAlias($name, $commandName);
        }
    }

    protected function registerAlias($name, $commandName)
    {
        if (!isset($this->commands[$commandName])) {
            $this->logger->warning('Command {name} does not exist', array(
                'name' => $commandName,
            ));

            return;
        }

        if (isset($this->aliases[$name])) {
            $this->logger->warning('Alias {name} is already registered', array(
                'name' => $name,
            ));

            return;
        }

        $this->aliases[$name] = $this->commands[$commandName];
        $this->commandAliases[$commandName][] = $name;
    }

    protected function getSandbox()
    {
        return SandboxFactory::getSandbox($this->getContext());
    }
}
