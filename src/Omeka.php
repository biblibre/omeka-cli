<?php

namespace OmekaCli;

use OmekaCli\Sandbox\SandboxFactory;
use OmekaCli\Context\ContextAwareInterface;
use OmekaCli\Context\ContextAwareTrait;

class Omeka implements ContextAwareInterface
{
    use ContextAwareTrait;

    public function __get($name)
    {
        return $this->getSandbox()->execute(function () use ($name) {
            return constant($name);
        });
    }

    public function getOption($name)
    {
        return $this->getSandbox()->execute(function () use ($name) {
            return get_option($name);
        });
    }

    public function getDbVersion()
    {
        return $this->getOption('omeka_version');
    }

    public function __call($name, $args)
    {
        return $this->getSandbox()->execute(function () use ($name, $args) {
            if (!is_callable($name)) {
                throw new \Exception("$name is not callable");
            }

            return call_user_func_array($name, $args);
        });
    }

    protected function getSandbox()
    {
        return SandboxFactory::getSandbox($this->getContext());
    }
}
