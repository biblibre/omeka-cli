<?php

if (file_exists($autoload = __DIR__ . '/../../autoload.php')) {
    require_once $autoload;
} else {
    require_once __DIR__ . '/vendor/autoload.php';
}

define('OMEKACLI_VERSION', '1.0.0-alpha.2');
