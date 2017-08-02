<?php

namespace OmekaCli\Command;

use OmekaCli\Application;
use OmekaCli\Logger;
use OmekaCli\Util\Sandbox;
use Zend_EventManager_StaticEventManager;

class Manager
{
    const EVENT_ID = 'OmekaCli';
    const COMMANDS_EVENT_NAME = 'commands';
    const COMMAND_INTERFACE = 'OmekaCli\Command\CommandInterface';

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

        $this->registerCommand('help',          'OmekaCli\Command\HelpCommand');
        $this->registerCommand('info',          'OmekaCli\Command\InfoCommand');
        $this->registerCommand('install',       'OmekaCli\Command\InstallCommand');
        $this->registerCommand('list',          'OmekaCli\Command\ListCommand');
        $this->registerCommand('options',       'OmekaCli\Command\OptionsCommand');
        $this->registerCommand('plugin',        'OmekaCli\Command\PluginCommand');
        $this->registerCommand('check-updates', 'OmekaCli\Command\CheckUpdatesCommand', array('chup'));
        $this->registerCommand('version',       'OmekaCli\Command\VersionCommand');

        $this->registerPluginCommands();
    }

    protected function registerPluginCommands()
    {
        $commands = $this->processEvent(self::COMMANDS_EVENT_NAME);
        foreach ($commands as $name => $commandSpec) {
            if (is_array($commandSpec)) {
                $aliases = isset($commandSpec['aliases']) ? $commandSpec['aliases'] : array();
                $class = isset($commandSpec['class']) ? $commandSpec['class'] : null;
            } else {
                $class = $commandSpec;
            }

            $this->registerCommand($name, $class, $aliases);
        }
    }

    protected function processEvent($eventName)
    {
        $items = array();

        if ($this->application->isOmekaInitialized()) {
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
     * @param string   $name    Command name
     * @param string   $class   the class name of the command
     * @param string[] $aliases aliases to this command
     */
    protected function registerCommand($name, $class, $aliases = array())
    {
        if (isset($this->commands[$name])) {
            $this->logger->warning('Command {name} is already registered', array(
                'name' => $name,
            ));

            return;
        }

        if ($this->isCommandClass($class)) {
            $command = new $class();
            $command->setLogger($this->logger);
            $this->commands[$name] = $command;
            $this->registerAliases($aliases, $name);
        }
    }

    protected function isCommandClass($class)
    {
        if (!is_string($class)) {
            return false;
        }

        $sandbox = new Sandbox();
        $result = $sandbox->run(function ($socket) use ($class) {
            if (!in_array(self::COMMAND_INTERFACE, class_implements($class))) {
                return 1;
            }

            return 0;
        });

        $exitCode = $result['exit_code'];
        if ($exitCode === 255) {
            $this->logger->error("Trying to load $class resulted in an error: {message}", array(
                'message' => $result['message'],
            ));
        } elseif ($exitCode === 1) {
            $this->logger->error("Class $class does not implement {interface}", array(
                'interface' => self::COMMAND_INTERFACE,
            ));
        } elseif ($exitCode === 0) {
            return true;
        }

        return false;
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
