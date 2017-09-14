<?php

namespace OmekaCli\Command;

use PDO;
use PDOException;
use OmekaCli\Application;
use OmekaCli\IniWriter;
use GetOptionKit\OptionCollection;

class InstallCommand extends AbstractCommand
{
    protected $options = array();

    protected static $defaultOptions = array(
        'db-host' => 'localhost',
        'db-user' => 'omeka',
        'db-pass' => '',
        'db-name' => 'omeka',
        'db-prefix' => '',
        'omeka-user-name' => 'admin',
        'omeka-user-password' => 'CHANGEME',
        'omeka-user-email' => 'admin@example.com',
        'omeka-site-title' => 'Omeka',
        'omeka-admin-email' => 'admin@example.com',
    );

    public function getOptionsSpec()
    {
        $cmdSpec = new OptionCollection();
        $cmdSpec->add('v|version:', 'Omeka version')
                ->isa('String');
        $cmdSpec->add('h|db-host:', 'database host')
                ->isa('String');
        $cmdSpec->add('u|db-user:', 'database user name')
                ->isa('String');
        $cmdSpec->add('p|db-pass:', 'database user password')
                ->isa('String');
        $cmdSpec->add('n|db-name:', 'database name')
                ->isa('String');
        $cmdSpec->add('db-prefix:', 'database prefix')
                ->isa('String');
        $cmdSpec->add('U|omeka-user-name:', 'Omeka superuser name')
                ->isa('String');
        $cmdSpec->add('P|omeka-user-password:', 'Omeka superuser password')
                ->isa('String');
        $cmdSpec->add('E|omeka-user-email:', 'Omeka superuser email')
                ->isa('String');
        $cmdSpec->add('T|omeka-site-title:', 'Omeka site title')
                ->isa('String');
        $cmdSpec->add('A|omeka-admin-email:', 'Omeka admin email')
                ->isa('String');

        return $cmdSpec;
    }

    public function getDescription()
    {
        return 'install Omeka';
    }

    public function getUsage()
    {
        return 'Usage:' . "\n"
             . "\tinstall [OPTIONS] DIR\n"
             . "\n"
             . "Arguments\n"
             . "\tDIR  the Omeka installation directory\n"
             . "\n"
             . "Install Omeka. This command needs all the requirements needed to install Omeka the classic way. See omeka.org for more informations.\n"
             . "\n"
             . "Options:\n"
             . "\t-v, --version TAG\n"
             . "\t\tgit tag refering to an Omeka version.\n"
             . "\t\tIf not given, the latest version will be installed\n"
             . "\n"
             . "\t-h, --db-host DB_HOST\n"
             . "\t\tdatabase host, default: '" . self::$defaultOptions['db-host'] . "'\n"
             . "\n"
             . "\t-u, --db-user DB_USER\n"
             . "\t\tdatabase user name, default: '" . self::$defaultOptions['db-user'] . "'\n"
             . "\n"
             . "\t-p, --db-pass DB_PASS\n"
             . "\t\tdatabase user password, default: '" . self::$defaultOptions['db-pass'] . "'\n"
             . "\n"
             . "\t-n, --db-name DB_NAME\n"
             . "\t\tdatabase name, default: '" . self::$defaultOptions['db-name'] . "'\n"
             . "\n"
             . "\t-n, --db-prefix DB_PREFIX\n"
             . "\t\tdatabase prefix, default: '" . self::$defaultOptions['db-prefix'] . "'\n"
             . "\n"
             . "\t-U, --omeka-user-name OMEKA_USER_NAME\n"
             . "\t\tOmeka superuser name, default: '" . self::$defaultOptions['omeka-user-name'] . "'\n"
             . "\n"
             . "\t-P, --omeka-user-password OMEKA_USER_PASSWORD\n"
             . "\t\tOmeka superuser password, default: '" . self::$defaultOptions['omeka-user-password'] . "'\n"
             . "\n"
             . "\t-E, --omeka-user-email OMEKA_USER_EMAIL\n"
             . "\t\tOmeka superuser email, default: '" . self::$defaultOptions['omeka-user-email'] . "'\n"
             . "\n"
             . "\t-T, --omeka-site-title OMEKA_SITE_TITLE\n"
             . "\t\tOmeka site title, default: '" . self::$defaultOptions['omeka-site-title'] . "'\n"
             . "\n"
             . "\t-A, --omeka-admin-email OMEKA_ADMIN_EMAIL\n"
             . "\t\tOmeka admin email, default '" . self::$defaultOptions['omeka-admin-email'] . "'\n";
    }

    public function run($options, $args, Application $application)
    {
        if (count($args) != 1) {
            $this->logger->error('Bad number of arguments');
            error_log($this->getUsage());

            return 1;
        }

        $dir = reset($args);
        $ver = null;
        $config = $options + self::$defaultOptions;

        $this->logger->info('downloading Omeka');
        if ($this->downloadOmeka($dir, $ver)) {
            $this->logger->error('Failed to download Omeka');

            return 1;
        }

        $this->logger->info('copying changeme files');
        if ($this->copyFiles($dir)) {
            $this->logger->error('Failed to copy .changeme files');

            return 1;
        }

        $this->logger->info('configuring database');
        $this->applyDbConfig($dir, $config);

        if (false === $this->createDatabase($config)) {
            $this->logger->error('Failed to create database');

            return 1;
        }

        $cwd = getcwd();
        chdir($dir);
        ob_start();
        $application->initialize();
        ob_end_clean();

        $this->logger->info('checking the database');
        if (!$this->isDatabaseEmpty($dir)) {
            $this->logger->error('database is not empty');

            return 1;
        }

        $this->logger->info('configuring Omeka');
        if (!$form = $this->applyOmekaConfig($config)) {
            $this->logger->error('something went wrong during Omeka configuration');

            return 1;
        }

        $this->logger->info('installing Omeka');
        if ($this->installOmeka($form)) {
            $this->logger->error('installation failed');

            return 1;
        }
        $this->logger->info('installation successful');

        return 0;
    }

    protected function downloadOmeka($dir, $ver)
    {
        if (file_exists($dir)) {
            if (!is_dir($dir)) {
                $this->logger->error('{dir} is not a directory', array('dir' => $dir));

                return 1;
            }

            if ((file_exists($dir . '/.git')
              && file_exists($dir . '/db.ini.changeme')
              && file_exists($dir . '/bootstrap.php'))) {
                $this->logger->info('Omeka already downloaded');

                return 0;
            }

            if (count(scandir($dir)) > 2) {
                $this->logger->error('{dir} is not empty', array('dir' => $dir));

                return 1;
            }
        }

        if (!isset($ver)) {
            $ver = rtrim(`git ls-remote -q --tags --refs https://github.com/omeka/Omeka | cut -f 2 | sed 's|refs/tags/||' | sort -rV | head -n1`);
        }

        $cmd = 'git clone --recursive --branch ' . escapeshellarg($ver) . ' https://github.com/omeka/Omeka ' . escapeshellarg($dir);
        exec($cmd, $out, $exitCode);
        if ($exitCode) {
            $this->logger->error('cannot clone Omeka repository');

            return 1;
        }

        return 0;
    }

    protected function copyFiles($dir)
    {
        $files = array(
            'db.ini',
            '.htaccess',
            'application/config/config.ini',
        );

        foreach ($files as $file) {
            $dest = "$dir/$file";
            $src = "$dest.changeme";
            if (!file_exists($dest)) {
                if (false === copy($src, $dest)) {
                    $this->logger->error('cannot copy {src} to {dest}', array('src' => $src, 'dest' => $dest));

                    return 1;
                }
            } else {
                $this->logger->info('{file} already exists', array('file' => $dest));
            }
        }

        return 0;
    }

    protected function applyDbConfig($dir, $config)
    {
        $dbini = $dir . '/db.ini';
        $db = parse_ini_file($dbini, true);
        $db['database']['host'] = $config['db-host'];
        $db['database']['username'] = $config['db-user'];
        $db['database']['password'] = $config['db-pass'];
        $db['database']['dbname'] = $config['db-name'];
        $db['database']['prefix'] = $config['db-prefix'];

        $iniWriter = new IniWriter($dbini);
        $iniWriter->writeArray($db);
    }

    protected function createDatabase($config)
    {
        $host = $config['db-host'];
        $user = $config['db-user'];
        $pass = $config['db-pass'];
        $name = $config['db-name'];

        try {
            $pdo = new PDO("mysql:host=$host", $user, $pass);
        } catch (PDOException $e) {
            $this->logger->error($e->getMessage());

            return false;
        }

        if (false === $pdo->query("CREATE DATABASE IF NOT EXISTS $name")) {
            $errorInfo = $pdo->errorInfo();
            $this->logger->error($errorInfo[2]);

            return false;
        }

        return true;
    }

    protected function isDatabaseEmpty($dir)
    {
        try {
            $db = get_db();
            $tables = $db->fetchAll("SHOW TABLES LIKE '{$db->prefix}options'");
            if (!empty($tables)) {
                return 0;
            }
        } catch (\Exception $e) {
            $this->logger->warning($e->getMessage());
        }

        return 1;
    }

    protected function applyOmekaConfig($config)
    {
        require_once FORM_DIR . '/Install.php';

        $data = array(
            'username' => $config['omeka-user-name'],
            'password' => $config['omeka-user-password'],
            'password_confirm' => $config['omeka-user-password'],
            'super_email' => $config['omeka-user-email'],
            'site_title' => $config['omeka-site-title'],
            'administrator_email' => $config['omeka-admin-email'],
            'tag_delimiter' => ',',
            'fullsize_constraint' => '800',
            'thumbnail_constraint' => '200',
            'square_thumbnail_constraint' => '200',
            'per_page_admin' => '10',
            'per_page_public' => '10',
        );

        $form = new \Omeka_Form_Install();
        $form->init();

        if (!$form->isValid($data)) {
            $message = "The following fields do not match a condition.\n";
            $errors = array_filter($form->getErrors());
            foreach ($errors as $field => $error) {
                $message .= sprintf('%s: %s', $field, implode(', ', $error)) . "\n";
            }

            return null;
        }

        return $form;
    }

    protected function installOmeka($form)
    {
        try {
            $installer = new \Installer_Default(get_db());
            $installer->setForm($form);
            \Zend_Controller_Front::getInstance()->getRouter()->addDefaultRoutes();
            $installer->install();
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());

            return 1;
        }

        return 0;
    }
}
