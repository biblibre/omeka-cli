<?php

namespace OmekaCli\Command;

use PDO;
use PDOException;
use OmekaCli\Application;
use OmekaCli\IniWriter;
use OmekaCli\Sandbox\SandboxFactory;
use OmekaCli\Context\Context;
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

    public function run($options, $args)
    {
        if (count($args) != 1) {
            $this->logger->error('Bad number of arguments');
            error_log($this->getUsage());

            return 1;
        }

        $omekaPath = reset($args);
        $version = null;
        if (isset($options['version'])) {
            $version = $options['version'];
        }

        $config = $options + self::$defaultOptions;

        if ($this->downloadOmeka($omekaPath, $version)) {
            $this->logger->error('Failed to download Omeka');

            return 1;
        }

        if ($this->copyFiles($omekaPath)) {
            $this->logger->error('Failed to copy .changeme files');

            return 1;
        }

        $this->applyDbConfig($omekaPath, $config);

        if (false === $this->createDatabase($config)) {
            $this->logger->error('Failed to create database');

            return 1;
        }

        if (!$this->isDatabaseEmpty($omekaPath)) {
            $this->logger->error('Database is not empty');

            return 1;
        }

        if (false === $this->installOmeka($omekaPath, $config)) {
            $this->logger->error('installation failed');

            return 1;
        }

        $this->logger->notice('Installation successful');

        return 0;
    }

    protected function downloadOmeka($omekaPath, $version)
    {
        if (file_exists($omekaPath)) {
            if (!is_dir($omekaPath)) {
                $this->logger->error('{dir} is not a directory', array('dir' => $omekaPath));

                return 1;
            }

            if ((file_exists($omekaPath . '/.git')
              && file_exists($omekaPath . '/db.ini.changeme')
              && file_exists($omekaPath . '/bootstrap.php'))) {
                $this->logger->info('Omeka already downloaded');

                return 0;
            }

            if (count(scandir($omekaPath)) > 2) {
                $this->logger->error('{dir} is not empty', array('dir' => $omekaPath));

                return 1;
            }
        }

        $repository = 'https://github.com/omeka/Omeka.git';

        if (!isset($version)) {
            $version = rtrim(`git ls-remote -q --tags --refs $repository | cut -f 2 | sed 's|refs/tags/||' | sort -rV | head -n1`);
        }

        $this->logger->info("Downloading Omeka from $repository...");
        $cmd = 'git clone --recursive --branch ' . escapeshellarg($version) . " $repository " . escapeshellarg($omekaPath);
        $descriptorspec = array(
            array('pipe', 'r'),
            array('pipe', 'w'),
            array('pipe', 'w'),
        );
        $proc = proc_open($cmd, $descriptorspec, $pipes);

        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);

        do {
            $read = array($pipes[1], $pipes[2]);
            $write = null;
            $except = null;

            if (false !== stream_select($read, $write, $except, 0, 200000)) {
                foreach ($read as $stream) {
                    if (!feof($stream)) {
                        $line = rtrim(fgets($stream));
                        if ($line) {
                            $this->logger->info($line);
                        }
                    }
                }
            }
        } while (!feof($pipes[1]) || !feof($pipes[2]));

        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($proc);
        if ($exitCode) {
            $this->logger->error('cannot clone Omeka repository');

            return 1;
        }

        return 0;
    }

    protected function copyFiles($omekaPath)
    {
        $files = array(
            'db.ini',
            '.htaccess',
            'application/config/config.ini',
        );

        $this->logger->info('Copying .changeme files');
        foreach ($files as $file) {
            $dest = "$omekaPath/$file";
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

    protected function applyDbConfig($omekaPath, $config)
    {
        $this->logger->info('Configuring database');

        $dbini = $omekaPath . '/db.ini';
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

    protected function isDatabaseEmpty($omekaPath)
    {
        try {
            $sandbox = SandboxFactory::getSandbox(new Context($omekaPath));
            $isEmpty = $sandbox->execute(function () {
                $db = get_db();
                $tables = $db->fetchAll("SHOW TABLES LIKE '{$db->prefix}options'");
                if (empty($tables)) {
                    return true;
                }

                return false;
            });
        } catch (\Exception $e) {
            $this->logger->warning($e->getMessage());
            $isEmpty = null;
        }

        return $isEmpty;
    }

    protected function installOmeka($omekaPath, $config)
    {
        $this->logger->info('Installing Omeka');

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

        $sandbox = SandboxFactory::getSandbox(new Context($omekaPath));
        try {
            $sandbox->execute(function () use ($data) {
                require_once FORM_DIR . '/Install.php';

                $form = new \Omeka_Form_Install();
                $form->init();

                if (!$form->isValid($data)) {
                    $message = "The following fields do not match a condition.\n";
                    $errors = array_filter($form->getErrors());
                    foreach ($errors as $field => $error) {
                        $message .= sprintf('%s: %s', $field, implode(', ', $error)) . "\n";
                    }
                    throw new \Exception($message);
                }

                $installer = new \Installer_Default(get_db());
                $installer->setForm($form);
                \Zend_Controller_Front::getInstance()->getRouter()->addDefaultRoutes();
                $installer->install();
            });
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());

            return false;
        }

        return true;
    }
}
