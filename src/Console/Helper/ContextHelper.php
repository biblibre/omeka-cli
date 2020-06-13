<?php

namespace OmekaCli\Console\Helper;

use OmekaCli\Context\ContextAwareInterface;
use OmekaCli\Context\ContextAwareTrait;
use Symfony\Component\Console\Helper\Helper;

class ContextHelper extends Helper implements ContextAwareInterface
{
    use ContextAwareTrait;

    public function getName()
    {
        return 'context';
    }

    public function getOmekaPath()
    {
        return $this->getContext()->getOmekaPath();
    }
}
