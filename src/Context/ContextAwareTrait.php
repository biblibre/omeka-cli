<?php

namespace OmekaCli\Context;

use OmekaCli\Sandbox\OmekaSandboxPool;

trait ContextAwareTrait
{
    protected $context;

    public function setContext(Context $context)
    {
        $this->context = $context;
    }

    public function getContext()
    {
        return $this->context;
    }

    public function getSandbox(Context $context = null)
    {
        if (!isset($context)) {
            $context = $this->getContext();
        }

        return OmekaSandboxPool::getSandbox($context);
    }
}
