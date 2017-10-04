<?php


require_once __DIR__ . '/../bootstrap.php';

$omeka_db_host = getenv('OMEKA_DB_HOST');
$omeka_db_user = getenv('OMEKA_DB_USER');
$omeka_db_pass = getenv('OMEKA_DB_PASS');
$omeka_db_name = getenv('OMEKA_DB_NAME');
$omeka_zip_path = getenv('OMEKA_ZIP_PATH');

try {
    $pdo = new PDO("mysql:host=$omeka_db_host", $omeka_db_user, $omeka_db_pass);
} catch (PDOException $e) {
    error_log($e->getMessage());
    exit(1);
}

if (false === $pdo->query("DROP DATABASE IF EXISTS $omeka_db_name")) {
    $errorInfo = $pdo->errorInfo();
    error_log($errorInfo[2]);
    exit(1);
}

if (false === $pdo->query("CREATE DATABASE $omeka_db_name")) {
    $errorInfo = $pdo->errorInfo();
    error_log($errorInfo[2]);
    exit(1);
}

fwrite(STDERR, "Retrieving Omeka from $omeka_zip_path...\n");
$tempfile = tempnam(sys_get_temp_dir(), 'omeka');
$fh = fopen($omeka_zip_path, 'r');
if ($fh === false) {
    error_log("Error: Failed to open file $omeka_zip_path");
    exit(1);
}
file_put_contents($tempfile, $fh);

$tempdir = rtrim(`mktemp -d --tmpdir omeka.XXXXXX`);
fwrite(STDERR, "Extracting Omeka into $tempdir...\n");
$zip = new ZipArchive();
$zip->open($tempfile);
$zip->extractTo($tempdir);
$zip->close();

unlink($tempfile);

$omekaPath = $tempdir . '/' . scandir($tempdir, SCANDIR_SORT_DESCENDING)[0];
$ini = parse_ini_file("$omekaPath/db.ini", true);
$ini['database']['host'] = $omeka_db_host;
$ini['database']['username'] = $omeka_db_user;
$ini['database']['password'] = $omeka_db_pass;
$ini['database']['dbname'] = $omeka_db_name;
$ini['database']['prefix'] = '';
(new OmekaCli\IniWriter("$omekaPath/db.ini"))->writeArray($ini);

$pid = pcntl_fork();
if ($pid) {
    pcntl_wait($status);
} else {
    fwrite(STDERR, "Run Omeka install process...\n");
    require "$omekaPath/bootstrap.php";

    $application = new Omeka_Application(APPLICATION_ENV);
    $bootstrap = $application->getBootstrap();
    $bootstrap->setOptions(array(
        'resources' => array(
            'theme' => array(
                'basePath' => THEME_DIR,
                'webBasePath' => WEB_THEME,
            ),
        ),
    ));
    $bootstrap->bootstrap('Db');
    $db = $bootstrap->getResource('Db');

    \Zend_Controller_Front::getInstance()->getRouter()->addDefaultRoutes();

    require_once FORM_DIR . '/Install.php';
    $form = new Omeka_Form_Install();
    $form->init();
    $form->isValid(array(
        'username' => 'admin',
        'password' => 'admin',
        'password_confirm' => 'admin',
        'super_email' => 'admin@example.com',
        'site_title' => 'Omeka for omeka-cli tests',
        'administrator_email' => 'admin@example.com',
        'tag_delimiter' => ',',
        'fullsize_constraint' => '800',
        'thumbnail_constraint' => '200',
        'square_thumbnail_constraint' => '200',
        'per_page_admin' => '10',
        'per_page_public' => '10',
    ));
    $installer = new Installer_Default($db);
    $installer->setForm($form);
    $installer->install();

    exit(0);
}

$fooPluginDir = __DIR__ . '/plugins/Foo';
`cp -r $fooPluginDir $omekaPath/plugins/`;

putenv("OMEKA_PATH=$omekaPath");

$pid = getmypid();
register_shutdown_function(function () use ($tempdir, $pid) {
    if ($pid === getmypid()) {
        OmekaCli\Sandbox\SandboxFactory::flush();
        rrmdir($tempdir);
    }
});

function rrmdir($dirname)
{
    $dir = opendir($dirname);
    if ($dir !== false) {
        while (false !== ($file = readdir($dir))) {
            if (($file != '.') && ($file != '..')) {
                $full = $dirname . '/' . $file;
                if (is_dir($full)) {
                    rrmdir($full);
                } else {
                    unlink($full);
                }
            }
        }
        closedir($dir);
    }
    rmdir($dirname);
}
