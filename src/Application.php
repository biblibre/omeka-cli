<?php

namespace OmekaCli;

use OmekaCli\Command\Manager;
use OmekaCli\Exception\BadUsageException;
use OmekaCli\Util\Sandbox;
use phpFastCache\CacheManager;
use GetOptionKit\OptionCollection;
use GetOptionKit\ContinuousOptionParser;
use GetOptionKit\Exception\InvalidOptionException;

class Application
{
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
            $appOptions = $parser->parse($argv);
        } catch (InvalidOptionException $e) {
            throw new BadUsageException($e->getMessage(), 0, $e);
        }

        while (!$parser->isEnd()) {
            $args[] = $parser->advance();
        }
        foreach ($appOptions->keys as $key => $appSpec) {
            $options[$key] = $appOptions->keys[$key]->value;
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

        $this->setupCache();
    }

    public function run()
    {
        $logger = $this->getLogger();

        $commands = $this->getCommandManager();
        $commandName = $this->getCommandName();

        if ($this->getOption('help') || !$commandName) {
            echo $this->longUsage();

            $exitCode = 0;
        } else {
            $exitCode = $this->runCommand();
        }

        return $exitCode;
    }

    public function isOmekaInitialized()
    {
        return isset($this->omekaApplication);
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
            $this->commands = new Manager($this, $this->getLogger());
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
            $logger->error('Command {command} does not exist', array('command' => $commandName));
            echo $this->usage();

            return 1;
        }

        $cmdSpec = $command->getOptionsSpec();

        $cmdArgs = array();
        $cmdOptions = array();
        $parser = new ContinuousOptionParser($cmdSpec);
        try {
            $cmdArgv = $parser->parse($this->args);
        } catch (\Exception $e) {
            $logger->error($e->getMessage());
            echo $command->getUsage();

            return 1;
        }

        while (!$parser->isEnd()) {
            $cmdArgs[] = $parser->advance();
        }
        foreach ($cmdArgv->keys as $key => $cmdSpec) {
            $cmdOptions[$key] = $cmdArgv->keys[$key]->value;
        }

        return $command->run($cmdOptions, $cmdArgs, $this);
    }

    protected function isOmekaDir($dir)
    {
        $sandbox = new Sandbox();
        $result = $sandbox->run(function () use ($dir) {
            include "$dir/bootstrap.php";
            if (defined('OMEKA_VERSION')) {
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

        if ($dir !== false && $dir !== '/') {
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
                    'webBasePath' => WEB_THEME,
                ),
            ),
        ));

        if (APPLICATION_ENV === 'testing') {
            \Zend_Controller_Front::getInstance()->getRouter()->addDefaultRoutes();
        }

        $application->getBootstrap()->getPluginResource('Options')->setInstallerRedirect(false);

        try {
            $bootstrap = $application->getBootstrap();
            $bootstrap->bootstrap('Db');
            $db = $bootstrap->getResource('Db');
        } catch (\Exception $e) {
            echo $e->getMessage() . PHP_EOL;
        }

        if (isset($db)) {
            try {
                $db->getTable('Options')->count();
                $application->initialize();
                $this->omekaApplication = $application;
            } catch (\Exception $e) {
                echo $e->getMessage() . PHP_EOL;
            }
        }
    }

    protected function setupCache()
    {
        $cacheHome = getenv('XDG_CACHE_HOME');
        if (empty($cacheHome)) {
            $cacheHome = getenv('HOME') . '/.cache';
        }
        $cacheDir = "$cacheHome/omeka-cli";

        CacheManager::setDefaultConfig(array(
            'path' => $cacheDir,
        ));
    }
}
