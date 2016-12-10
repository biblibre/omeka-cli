<?php

namespace OmekaCli;

use OmekaCli\Command\Manager as CommandManager;
use OmekaCli\Exception\BadUsageException;
use OmekaCli\Util\Sandbox;

class Application
{
    protected static $optionsSpec = array(
        'omeka-path' => array(
            'short' => 'C',
            'parameter' => true,
        ),
        'help' => array(
            'short' => 'h',
            'long' => 'help',
        ),
    );

    protected $logger;
    protected $commands;
    protected $omekaApplication;

    protected $options;
    protected $args;

    /**
     * Create a new Application from global $argv.
     *
     * @return self
     */
    public static function fromArgv()
    {
        global $argv;

        try {
            $parser = new CommandLineParser(self::$optionsSpec);
            $result = $parser->parse($argv);
            $options = $result['options'];
            $args = $result['args'];
        } catch (\Exception $e) {
            throw new BadUsageException($e->getMessage(), 0, $e);
        }

        return new self($options, $args);
    }

    public static function usage()
    {
        $usage = "Usage:\n"
            . "\tomeka-cli --help\n"
            . "\tomeka-cli [-C <omeka-path>] COMMAND [ARGS...]\n"
            . "\n";

        return $usage;
    }


    public function __construct($options, $args)
    {
        $this->options = $options;
        $this->args = $args;
    }

    public function initialize()
    {
        $omekaPath = $this->getOption('omeka-path');
        if ($omekaPath) {
            if (!$this->isOmekaDir($omekaPath)) {
                throw new \Exception("$omekaPath is not an Omeka directory");
            }
        } else {
            $omekaPath = $this->searchOmekaDir();
        }

        if ($omekaPath) {
            require_once "$omekaPath/bootstrap.php";
            $this->initializeOmeka();
        }
    }

    public function run()
    {
        $logger = $this->getLogger();

        $commands = $this->getCommandManager();
        $commandName = $this->getCommandName();

        if ($this->getOption('help') || !$commandName) {
            print $this->longUsage();
            return 0;
        }

        return $this->runCommand();

    }

    public function isOmekaInitialized()
    {
        return isset($this->omekaApplication);
    }

    public function getLogger()
    {
        if (!isset($this->logger)) {
            $this->logger = new Logger;
        }

        return $this->logger;
    }

    public function getCommandManager()
    {
        if (!isset($this->commands)) {
            $this->commands = new CommandManager($this, $this->logger);
        }

        return $this->commands;
    }

    protected function getCommandName()
    {
        return reset($this->args);
    }

    protected function getOption($name, $default = null)
    {
        if (isset($this->options[$name])) {
            return $this->options[$name];
        }
    }

    protected function longUsage()
    {
        $usage = self::usage();

        $commands = $this->getCommandManager();
        if (isset($commands)) {
            $usage .= "Available commands:\n\n";
            foreach ($commands->getAll() as $name => $command) {
                $usage .= "\t$name";
                $description = $command->getDescription();
                if ($description) {
                    $usage .= " -- $description";
                }
                $usage .= "\n";
            }
        }

        $usage .= "\n";

        return $usage;
    }

    protected function runCommand()
    {
        $commands = $this->getCommandManager();
        $commandName = $this->getCommandName();
        $logger = $this->getLogger();

        $command = $commands->get($commandName);
        if (!isset($command)) {
            $logger->error('Command {command} does not exist', array(
                'command' => $commandName,
            ));
            return 1;
        }

        $exitCode = 0;
        try {
            $parser = new CommandLineParser($command->getOptionsSpec());
            $result = $parser->parse($this->args);
            $exitCode = $command->run($result['options'], $result['args'], $this);
        } catch (BadUsageException $e) {
            $logger->error($e->getMessage());
            print $command->getUsage();
            return 1;
        }

        return $exitCode;
    }

    protected function isOmekaDir($dir)
    {
        $sandbox = new Sandbox;
        $result = $sandbox->run(function() use($dir) {
            include "$dir/bootstrap.php";
            if (defined("OMEKA_VERSION")) {
                return 0;
            }
            return 1;
        });

        return $result['exit_code'] === 0 ? true : null;
    }

    protected function searchOmekaDir()
    {
        $dir = realpath('.');
        while ($dir !== false && $dir !== '/' && !$this->isOmekaDir($dir)) {
            $dir = realpath($dir . '/..');
        }

        if (!$dir !== false && $dir !== '/') {
            return $dir;
        }
    }

    protected function initializeOmeka()
    {
        $application = new \Omeka_Application(APPLICATION_ENV);
        $application->getBootstrap()->setOptions(array(
            'resources' => array(
                'theme' => array(
                    'basePath' => THEME_DIR,
                    'webBasePath' => WEB_THEME
                )
            )
        ));
        $application->initialize();
        $this->omekaApplication = $application;
    }
}
