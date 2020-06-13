<?php

namespace OmekaCli\Command;

use OmekaCli\Context\Context;
use OmekaCli\IniWriter;
use PDO;
use PDOException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class InstallCommand extends AbstractCommand
{
    protected function configure()
    {
        $this->setName('install');
        $this->setDescription('install Omeka');
        $this->addOption('branch', 'b', InputOption::VALUE_REQUIRED, 'Git branch or tag to use. If not given, the latest version will be installed');
        $this->addOption('db-host', 'H', InputOption::VALUE_REQUIRED, 'database host', 'localhost');
        $this->addOption('db-user', 'u', InputOption::VALUE_REQUIRED, 'database user name', 'omeka');
        $this->addOption('db-pass', 'p', InputOption::VALUE_REQUIRED, 'database user password', '');
        $this->addOption('db-name', 'N', InputOption::VALUE_REQUIRED, 'database name', 'omeka');
        $this->addOption('db-prefix', null, InputOption::VALUE_REQUIRED, 'database prefix', '');
        $this->addOption('omeka-user-name', 'U', InputOption::VALUE_REQUIRED, 'Omeka superuser name', 'admin');
        $this->addOption('omeka-user-password', 'P', InputOption::VALUE_REQUIRED, 'Omeka superuser password', 'CHANGEME');
        $this->addOption('omeka-user-email', 'E', InputOption::VALUE_REQUIRED, 'Omeka superuser email', 'admin@example.com');
        $this->addOption('omeka-site-title', 'T', InputOption::VALUE_REQUIRED, 'Omeka site title', 'Omeka');
        $this->addOption('omeka-admin-email', 'A', InputOption::VALUE_REQUIRED, 'Omeka admin email', 'admin@example.com');
        $this->addArgument('omeka-path', InputArgument::REQUIRED, 'the Omeka installation directory');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $omekaPath = $input->getArgument('omeka-path');
        $branch = $input->getOption('branch');

        $stderr = $this->getStderr();

        if ($this->downloadOmeka($omekaPath, $branch)) {
            $stderr->writeln('Error: Failed to download Omeka');

            return 1;
        }

        if ($this->copyFiles($omekaPath)) {
            $stderr->writeln('Error: Failed to copy .changeme files');

            return 1;
        }

        $config = $input->getOptions();

        $this->applyDbConfig($omekaPath, $config);

        if (false === $this->createDatabase($config)) {
            $stderr->writeln('Error: Failed to create database');

            return 1;
        }

        if (!$this->isDatabaseEmpty($omekaPath)) {
            $stderr->writeln('Error: Database is not empty');

            return 1;
        }

        if (false === $this->installOmeka($omekaPath, $config)) {
            $stderr->writeln('Error: Installation failed');

            return 1;
        }

        $stderr->writeln('Installation successful');

        return 0;
    }

    protected function downloadOmeka($omekaPath, $branch)
    {
        $stderr = $this->getStderr();

        if (file_exists($omekaPath)) {
            if (!is_dir($omekaPath)) {
                $stderr->writeln(sprintf('Error: %s is not a directory', $omekaPath));

                return 1;
            }

            if ((file_exists($omekaPath . '/.git')
              && file_exists($omekaPath . '/db.ini.changeme')
              && file_exists($omekaPath . '/bootstrap.php'))) {
                if ($stderr->isVerbose()) {
                    $stderr->writeln('Omeka already downloaded');
                }

                return 0;
            }

            if (count(scandir($omekaPath)) > 2) {
                $stderr->writeln(sprintf('Error: %s is not empty', $omekaPath));

                return 1;
            }
        }

        $repository = 'https://github.com/omeka/Omeka.git';

        if (!isset($branch)) {
            $branch = rtrim(`git ls-remote -q --tags --refs $repository | cut -f 2 | sed 's|refs/tags/||' | sort -rV | head -n1`);
        }

        if ($stderr->isVerbose()) {
            $stderr->writeln("Downloading Omeka from $repository...");
        }

        $cmd = 'git clone --recursive --branch ' . escapeshellarg($branch) . " $repository " . escapeshellarg($omekaPath);
        $descriptorspec = [
            ['pipe', 'r'],
            ['pipe', 'w'],
            ['pipe', 'w'],
        ];
        $proc = proc_open($cmd, $descriptorspec, $pipes);

        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);

        do {
            $read = [$pipes[1], $pipes[2]];
            $write = null;
            $except = null;

            if (false !== stream_select($read, $write, $except, 0, 200000)) {
                foreach ($read as $stream) {
                    if (!feof($stream)) {
                        $line = fgets($stream);
                        if ($stderr->isVerbose()) {
                            $stderr->write($line);
                        }
                    }
                }
            }
        } while (!feof($pipes[1]) || !feof($pipes[2]));

        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($proc);
        if ($exitCode) {
            $stderr->writeln('Error: Cannot clone Omeka repository');

            return 1;
        }

        return 0;
    }

    protected function copyFiles($omekaPath)
    {
        $stderr = $this->getStderr();

        $files = [
            'db.ini',
            '.htaccess',
            'application/config/config.ini',
        ];

        if ($stderr->isVerbose()) {
            $stderr->writeln('Copying .changeme files');
        }

        foreach ($files as $file) {
            $dest = "$omekaPath/$file";
            $src = "$dest.changeme";
            if (!file_exists($dest)) {
                if (false === copy($src, $dest)) {
                    $stderr->writeln(sprintf('Error: Cannot copy %1$s to %2$s', $src, $dest));

                    return 1;
                }
            } else {
                $stderr->writeln(sprintf('Error: %s already exists', $dest));
            }
        }

        return 0;
    }

    protected function applyDbConfig($omekaPath, $config)
    {
        $stderr = $this->getStderr();
        if ($stderr->isVerbose()) {
            $stderr->writeln('Configuring database');
        }

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
        $stderr = $this->getStderr();

        $host = $config['db-host'];
        $user = $config['db-user'];
        $pass = $config['db-pass'];
        $name = $config['db-name'];

        try {
            $pdo = new PDO("mysql:host=$host", $user, $pass);
        } catch (PDOException $e) {
            $stderr->writeln(sprintf('Error: %s', $e->getMessage()));

            return false;
        }

        if (false === $pdo->query("CREATE DATABASE IF NOT EXISTS $name")) {
            $errorInfo = $pdo->errorInfo();
            $stderr->writeln(sprintf('Error: %s', $errorInfo[2]));

            return false;
        }

        return true;
    }

    protected function isDatabaseEmpty($omekaPath)
    {
        $stderr = $this->getStderr();

        try {
            $sandbox = $this->getSandbox(new Context($omekaPath));
            $isEmpty = $sandbox->execute(function () {
                $db = get_db();
                $tables = $db->fetchAll("SHOW TABLES LIKE '{$db->prefix}options'");
                if (empty($tables)) {
                    return true;
                }

                return false;
            });
        } catch (\Exception $e) {
            $stderr->writeln(sprintf('Warning: %s', $e->getMessage()));
            $isEmpty = null;
        }

        return $isEmpty;
    }

    protected function installOmeka($omekaPath, $config)
    {
        $stderr = $this->getStderr();
        if ($stderr->isVerbose()) {
            $stderr->writeln('Installing Omeka');
        }

        $data = [
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
        ];

        $sandbox = $this->getSandbox(new Context($omekaPath));
        try {
            $sandbox->execute(function () use ($data) {
                require_once constant('FORM_DIR') . '/Install.php';

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
            $stderr->writeln(sprintf('Error: %s', $e->getMessage()));

            return false;
        }

        return true;
    }
}
