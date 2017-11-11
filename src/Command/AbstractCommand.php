<?php

namespace OmekaCli\Command;

use OmekaCli\Context\Context;
use OmekaCli\Omeka;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractCommand extends Command
{
    protected $omeka;

    protected $input;
    protected $output;

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;
    }

    protected function getInput()
    {
        return $this->input;
    }

    protected function getOutput()
    {
        return $this->output;
    }

    protected function getStderr()
    {
        if ($this->output instanceof ConsoleOutputInterface) {
            return $this->output->getErrorOutput();
        }

        return $this->output;
    }

    protected function getContext()
    {
        return $this->getHelper('context')->getContext();
    }

    protected function getSandbox(Context $context = null)
    {
        return $this->getHelper('context')->getSandbox($context);
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
