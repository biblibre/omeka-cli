<?php

namespace OmekaCli\Command;

use Psr\Log\LoggerAwareTrait;
use OmekaCli\Context\ContextAwareTrait;
use OmekaCli\Sandbox\SandboxFactory;

class Proxy implements CommandInterface
{
    use LoggerAwareTrait;
    use ContextAwareTrait;

    protected $commandInfo;
    protected $command;
    protected $commandManager;

    public function __construct($commandInfo)
    {
        $this->commandInfo = $commandInfo;
    }

    public function setCommandManager($commandManager)
    {
        $this->commandManager = $commandManager;
    }

    public function getOptionsSpec()
    {
        return $this->call(__FUNCTION__);
    }

    public function getDescription()
    {
        return $this->call(__FUNCTION__);
    }

    public function getUsage()
    {
        return $this->call(__FUNCTION__);
    }

    public function run($options, $args)
    {
        return $this->call(__FUNCTION__, array($options, $args));
    }

    public function __call($name, $args)
    {
        return $this->call($name, $args);
    }

    protected function call($name, $args = array())
    {
        $c = function () use ($name, $args) {
            if (!isset($this->command)) {
                $command = new $this->commandInfo['class']();
                $command->setLogger($this->logger);
                $command->setContext($this->getContext());
                $command->setCommandManager($this->commandManager);

                $this->command = $command;
            }

            $callback = array($this->command, $name);
            if (!is_callable($callback)) {
                throw new \Exception(sprintf('Method %s does not exist', $this->commandInfo['class'] . '::' . $name));
            }

            return call_user_func_array($callback, $args);
        };

        if ($this->commandInfo['is_plugin']) {
            $sandbox = $this->getSandbox();
            $return = $sandbox->execute($c);
        } else {
            $return = call_user_func($c);
        }

        return $return;
    }

    protected function getSandbox()
    {
        return SandboxFactory::getSandbox($this->getContext());
    }
}
