<?php

namespace OmekaCli;

use GetOptionKit\OptionCollection;
use GetOptionKit\ContinuousOptionParser;
use GetOptionKit\Exception\InvalidOptionException;
use OmekaCli\Command\Manager;
use OmekaCli\Context\Context;
use OmekaCli\Exception\BadUsageException;
use OmekaCli\Sandbox\OmekaSandbox;

class Application
{
    protected $logger;
    protected $commands;
    protected $omekaPath;

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

        $appSpec = new OptionCollection();
        $appSpec->add('h|help', 'show help');
        $appSpec->add('C|omeka-path:', 'path to Omeka')
                ->isa('String');
        $appSpec->add('v|verbose', 'verbose mode')
            ->isa('Number')
            ->incremental();
        $appSpec->add('q|quiet', 'quiet mode')
            ->isa('Number')
            ->incremental();

        $args = array();
        $options = array();
        $parser = new ContinuousOptionParser($appSpec);
        try {
            $result = $parser->parse($argv);
        } catch (InvalidOptionException $e) {
            throw new BadUsageException($e->getMessage(), 0, $e);
        }

        $options = $result->toArray();
        $args = array();
        while (!$parser->isEnd()) {
            $args[] = $parser->advance();
        }

        return new self($options, $args);
    }

    public static function usage()
    {
        $usage = "Usage:\n"
            . "\tomeka-cli [-h | --help]\n"
            . "\tomeka-cli [-C <omeka-path>] [-v | --verbose] [-q | --quiet]\n"
            . "\t          <command> [<args>]\n"
            . "\n"
            . "Options:\n"
            . "\t-h, --help       Print this help and exit\n"
            . "\t-C <omeka-path>  Tells where Omeka is installed. If omitted,\n"
            . "\t                 Omeka will be searched in current directory\n"
            . "\t                 and parent directories\n"
            . "\t-v, --verbose    Repeatable. Increase verbosity\n"
            . "\t-q, --quiet      Repeatable. Decrease verbosity\n";

        return $usage;
    }

    public function __construct($options = array(), $args = array())
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

        $this->omekaPath = $omekaPath;
    }

    public function run()
    {
        $commandName = $this->getCommandName();

        if ($this->getOption('help') || !$commandName) {
            echo $this->longUsage();

            return 0;
        }

        return $this->runCommand($commandName);
    }

    public function getLogger()
    {
        if (!isset($this->logger)) {
            $this->logger = new Logger();
            $verbose = $this->getOption('verbose', 0);
            $quiet = $this->getOption('quiet', 0);
            $verbosity = Logger::DEFAULT_VERBOSITY + $verbose - $quiet;
            $this->logger->setVerbosity($verbosity);
        }

        return $this->logger;
    }

    public function getCommandManager()
    {
        if (!isset($this->commands)) {
            $omekaPath = $this->omekaPath;
            $commands = new Manager();
            $commands->setLogger($this->getLogger());
            $commands->setContext(new Context($omekaPath));
            $commands->initialize();

            $this->commands = $commands;
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
        $usage .= "\nAvailable commands:\n\n";
        foreach ($commands->getCommandsNames() as $name) {
            $usage .= "\t$name";
            $command = $commands->getCommand($name);
            $description = $command->getDescription();
            if ($description) {
                $usage .= " -- $description";
            }
            $usage .= "\n";
        }

        $usage .= "\n";

        return $usage;
    }

    protected function runCommand($commandName)
    {
        $commands = $this->getCommandManager();

        try {
            return $commands->run($commandName, $this->args);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
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
        });

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
}
