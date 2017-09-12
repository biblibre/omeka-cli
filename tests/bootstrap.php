<?php

use OmekaCli\Application;

require_once __DIR__ . '/../bootstrap.php';

define('APPLICATION_ENV', 'testing');

$omeka_path = getenv('OMEKA_PATH');
if (!$omeka_path) {
    exit("Error: OMEKA_PATH environment variable not defined.\n");
}

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
