<?php

namespace OmekaCli\Command;

use OmekaCli\Application;
use OmekaCli\Logger;
use Zend_EventManager_StaticEventManager;

class Manager
{
    const EVENT_ID = 'OmekaCli';
    const COMMANDS_EVENT_NAME = 'commands';

    protected $application;
    protected $logger;

    protected $commands;
    protected $aliases;
    protected $commandAliases;

    public function __construct(Application $application, Logger $logger)
    {
        $this->application = $application;
        $this->logger = $logger;

        $this->init();
    }

    public function getAll()
    {
        return $this->commands;
    }

    public function get($name)
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

    public function run($commandName, $options = array(), $args = array())
    {
        $command = $this->get($commandName);
        if (!$command) {
            throw new \Exception("Unknown command $commandName");
        }

        return $command->run($options, $args, $this->application);
    }

    public function getCommandUsage($commandName)
    {
        $command = $this->get($commandName);
        if (!$command) {
            throw new \Exception("Unknown command $commandName");
        }
    }

    protected function init()
    {
        $this->commands = array();

        $this->registerCommand('version', new VersionCommand);
        $this->registerCommand('help', new HelpCommand);
        $this->registerCommand('list', new ListCommand);

        $this->registerCommand('plugin-download', new Plugin\DownloadCommand());
        $this->registerAlias('dl', 'plugin-download');

        $this->registerPluginCommands();
    }

    protected function registerPluginCommands()
    {
        $commands = $this->processEvent(self::COMMANDS_EVENT_NAME);
        foreach ($commands as $name => $command) {
            if (is_array($command)) {
                $aliases = isset($command['aliases']) ? $command['aliases'] : array();
                $command = isset($command['command']) ? $command['command'] : null;
            }

            $this->registerCommand($name, $command, $aliases);
        }
    }

    protected function processEvent($eventName)
    {
        $items = array();

        if ($this->application->isOmekaInitialized()) {
            $events = Zend_EventManager_StaticEventManager::getInstance();
            $listeners = $events->getListeners(self::EVENT_ID, $eventName);

            if (false !== $listeners) {
                foreach ($listeners->getIterator() as $listener) {
                    $items = array_merge($items, $listener->call());
                }
            }
        }

        return $items;
    }

    /**
     * Register a new command
     *
     * @param string $name     Command name
     * @param mixed  $command  The command object or an array containing the
     *                         keys 'command' and 'aliases'.
     */
    protected function registerCommand($name, $command, $aliases = array())
    {
        if (!$command instanceof CommandInterface) {
            $this->logger->warning('Command {name} does not implement CommandInterface', array(
                'name' => $name,
            ));

            return;
        }

        if (isset($this->commands[$name])) {
            $this->logger->warning('Command {name} is already registered', array(
                'name' => $name,
            ));

            return;
        }

        $this->commands[$name] = $command;
        $this->registerAliases($aliases, $name);
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
}
