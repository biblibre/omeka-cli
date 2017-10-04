<?php

namespace OmekaCli\Context;

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
}
