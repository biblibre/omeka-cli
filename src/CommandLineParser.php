<?php

namespace OmekaCli;

class CommandLineParser
{
    protected $options;

    public function __construct(array $options = array())
    {
        $this->options = $options;
    }

    public function parse(array $args = null)
    {
        if (!isset($args)) {
            global $argv;
            $args = $argv;
        }

        array_shift($args);

        $result = array(
            'options' => array(),
            'args' => array(),
        );

        $i = 1;
        reset($args);
        while (false !== current($args)) {
            $arg = current($args);

            if (empty($arg)) {
                next($args);
                continue;
            }

            if ($arg[0] !== '-') {
                break;
            }

            if ($arg == '--') {
                next($args);
                break;
            }

            if (!$this->parseOption($arg, $args, $result)) {
                throw new \Exception("Unknown option $arg");
            }

            next($args);
        }

        while (false !== current($args)) {
            $arg = current($args);

            if (empty($arg)) {
                next($args);
                continue;
            }

            $result['args'][] = $arg;

            next($args);
        }

        return $result;
    }

    protected function parseOption($arg, &$args, &$result)
    {
        foreach ($this->options as $name => $option) {
            if (0 === substr_compare($arg, '--', 0, 2)) {
                if ($this->parseLongOption($name, $option, $arg, $args, $result)) {
                    return true;
                }
            } else {
                if ($this->parseShortOption($name, $option, $arg, $args, $result)) {
                    return true;
                }
            }
        }

        return false;
    }

    protected function parseLongOption($name, $option, $arg, &$args, &$result)
    {
        if (!isset($option['long'])) {
            return false;
        }

        list($argName, $argValue) = array_pad(explode('=', substr($arg, 2)), 2, null);

        if ($argName === $option['long']) {
            if (isset($option['parameter']) && $option['parameter']) {
                if (!$argValue) {
                    $argValue = next($args);
                    if (!$argValue) {
                        throw new \Exception("Missing parameter for option --$argName");
                    }
                }

                $result['options'][$name] = $argValue;
            } else {
                if (isset($result['options'][$name])) {
                    $result['options'][$name] += 1;
                } else {
                    $result['options'][$name] = 1;
                }
            }

            return true;
        }

        return false;
    }

    protected function parseShortOption($name, $option, $arg, &$args, &$result)
    {
        if (!isset($option['short'])) {
            return false;
        }

        $argName = substr($arg, 1, 1);
        $argValue = substr($arg, 2);

        if ($argName === $option['short']) {
            if (isset($option['parameter']) && $option['parameter']) {
                if (!$argValue) {
                    $argValue = next($args);
                    if (!$argValue) {
                        throw new \Exception("Missing parameter for option -$argName");
                    }
                }

                $result['options'][$name] = $argValue;
            } else {
                if (isset($result['options'][$name])) {
                    $result['options'][$name] += 1;
                } else {
                    $result['options'][$name] = 1;
                }
            }

            return true;
        }

        return false;
    }
}
