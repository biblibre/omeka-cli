<?php

namespace OmekaCli\Sandbox;

use OmekaCli\Context\Context;

class SandboxFactory
{
    protected static $sandboxes = array();

    public static function getSandbox(Context $context)
    {
        $omekaPath = $context->getOmekaPath();
        $key = $omekaPath ?: '';

        if (!isset(self::$sandboxes[$key])) {
            $sandbox = new OmekaSandbox();
            $sandbox->setContext($context);

            self::$sandboxes[$key] = $sandbox;
        }

        return self::$sandboxes[$key];
    }

    public static function flush()
    {
        self::$sandboxes = array();
    }
}
