<?php

namespace OmekaCli\Command;

use GetOptionKit\OptionCollection;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use OmekaCli\Sandbox\SandboxFactory;
use OmekaCli\Context\Context;
use OmekaCli\Context\ContextAwareTrait;
use OmekaCli\Omeka;

abstract class AbstractCommand implements CommandInterface
{
    use LoggerAwareTrait;
    use ContextAwareTrait;

    protected $commandManager;
    protected $omeka;

    public function __construct()
    {
        $this->setLogger(new NullLogger());
        $this->setContext(new Context());
    }

    public function setCommandManager($commandManager)
    {
        $this->commandManager = $commandManager;
    }

    public function getOptionsSpec()
    {
        return new OptionCollection();
    }

    public function getDescription()
    {
        return null;
    }

    public function getUsage()
    {
        return null;
    }

    protected function getSandbox()
    {
        return SandboxFactory::getSandbox($this->getContext());
    }

    protected function getOmeka()
    {
        if (!isset($this->omeka)) {
            $omeka = new Omeka();
            $omeka->setContext($this->getContext());

            $this->omeka = $omeka;
        }

        return $this->omeka;
    }
}
