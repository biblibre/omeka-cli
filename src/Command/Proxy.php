<?php

namespace OmekaCli\Command;

use OmekaCli\Context\Context;
use OmekaCli\Context\ContextAwareTrait;
use OmekaCli\Sandbox\OmekaSandbox;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Proxy extends AbstractCommand
{
    use ContextAwareTrait;

    protected static $commands = array();

    protected $class;

    public function __construct($name = null, $class, Context $context)
    {
        $this->class = $class;
        $this->setContext($context);

        parent::__construct($name);
    }

    protected function configure()
    {
        $this->setName($this->call('getName'));
        $this->setDescription($this->call('getDescription'));
        $this->setHelp($this->call('getHelp'));
        $this->setAliases($this->call('getAliases'));
        foreach ($this->call('getUsages') as $usage) {
            $this->addUsage($usage);
        }
    }

    public function setApplication(\Symfony\Component\Console\Application $application = null)
    {
        parent::setApplication($application);
        $this->call(__FUNCTION__, array($application));
    }

    public function setHelperSet(\Symfony\Component\Console\Helper\HelperSet $helperSet)
    {
        parent::setHelperSet($helperSet);
        $this->call(__FUNCTION__, array($helperSet));
    }

    public function setDefinition($definition)
    {
        parent::setDefinition($definition);
        $this->call(__FUNCTION__, array($definition));
    }

    public function isEnabled()
    {
        return $this->call(__FUNCTION__);
    }

    public function run(InputInterface $input, OutputInterface $output)
    {
        $sandbox = new OmekaSandbox();
        $sandbox->setContext($this->getContext());
        $status = $sandbox->execute(function () use ($input, $output) {
            $command = $this->getCommand();
            $command->setApplication($this->getApplication());

            return $command->run($input, $output);
        }, OmekaSandbox::ENV_SHORTLIVED);

        return $status;
    }

    public function mergeApplicationDefinition($mergeArgs = true)
    {
        parent::mergeApplicationDefinition($mergeArgs);
        $this->call(__FUNCTION__, array($mergeArgs));
    }

    public function getSynopsis($short = false)
    {
        return $this->call(__FUNCTION__, array($short));
    }

    protected function getCommand()
    {
        if (!isset(self::$commands[$this->class])) {
            $class = $this->class;
            $command = new $class();

            self::$commands[$this->class] = $command;
        }

        return self::$commands[$this->class];
    }

    protected function call($name, $args = array())
    {
        $c = function () use ($name, $args) {
            $command = $this->getCommand();

            $callback = array($command, $name);
            if (!is_callable($callback)) {
                throw new \Exception(sprintf('Method %s does not exist', $this->class . '::' . $name));
            }

            return call_user_func_array($callback, $args);
        };

        return $this->getSandbox()->execute($c);
    }
}
