<?php

require_once __DIR__ . '/../vendor/autoload.php';

define('OMEKACLI_PATH', __DIR__ . '/..');
define('BACKUPS_DIR', getenv('HOME') . '/.omeka-cli/backups');
define('OMEKACLI_VERSION', '0.15.0');

use OmekaCli\Application;

define('APPLICATION_ENV', 'testing');

$omeka_path = getenv('OMEKA_PATH');
if (!$omeka_path)
    exit("Error: OMEKA_PATH environment variable not defined.\n");

$application = new Application(
    array('omeka-path' => $omeka_path),
    array()
);
$application->initialize();

define('NO_PROMPT', true);

Zend_Registry::set(
    'omeka-cli-application',
    $application
);
