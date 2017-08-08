<?php

namespace OmekaCli\Command;

use OmekaCli\Application;
use OmekaCli\UIUtils;

class InstallCommand extends AbstractCommand
{
    public function getDescription()
    {
        return 'install Omeka';
    }

    public function getUsage()
    {
        $usage = 'Usage:' . PHP_EOL
               . '    install DIR [VERSION]' . PHP_EOL
               . PHP_EOL
               . 'Arguments' . PHP_EOL
               . '    DIR  the Omeka installation directory' . PHP_EOL
               . '    VERSION  the Omeka version to install' . PHP_EOL
               . PHP_EOL
               . 'Install Omeka. This command needs all the requierements '
               . 'needed to install Omeka the classic way. See omeka.org '
               . 'for more informations.' . PHP_EOL;

        return $usage;
    }

    public function run($options, $args, Application $application)
    {
        if (count($args) == 2) {
            $ver = array_pop($args);
        } else if (count($args) != 1) {
            echo $this->getUsage();
            return 1;
        } else {
            $ver = null;
        }
        $dir = array_pop($args);

        $this->logger->info('downloading Omeka');
        if ($this->dlOmeka($dir, $ver)) {
            $this->logger->error('installation failed');
            return 1;
        }

        $this->logger->info('copying changeme files');
        if ($this->copyFiles($dir)) {
            $this->logger->error('installation failed');
            return 1;
        }

        $this->logger->info('configuring database');
        $this->configDb($dir);

        $this->logger->info('checking the database');
        if ($this->checkDb($dir)) {
            $this->logger->error('installation failed');
            return 1;
        }

        $this->logger->info('configuring Omeka');
        $form = $this->configOmeka($form);

        $this->logger->info('installing Omeka');
        if ($this->installOmeka($form)) {
            $this->logger->error('installation failed');
            return 1;
        }
        $this->logger->info('installation successful');

        return 0;
    }

    protected function dlOmeka($dir, $ver)
    {
        if (!is_dir($dir) || count(scandir($dir)) == 2) {
            if (isset($ver)) {
                $cmd = 'git clone -b ' . $ver . ' https://github.com/omeka/Omeka ' . $dir;
            } else {
                $lastVersion = shell_exec('git ls-remote --tags https://github.com/omeka/Omeka | grep -ho \'v[0-9]\+\(\.[0-9]\+\)*\' | tail -n1 | tr -d \'\n\'');
                $cmd = 'git clone -b ' . $lastVersion . ' https://github.com/omeka/Omeka ' . $dir;
            }
            exec($cmd, $out, $ans);
            if ($ans) {
                $this->logger->error('cannot clone Omeka repository');
                return 1;
            }
        } elseif (!(file_exists($dir . '/.git')
              &&    file_exists($dir . '/db.ini.changeme')
              &&    file_exists($dir . '/bootstrap.php'))) {
            $this->logger->info('Omeka already downloaded');
        } else {
            $this->logger->error($dir . ' not empty');
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
            if (!file_exists($dir . '/'. $file)) {
                $ans = copy($dir . '/'. $file . '.changeme',
                            $dir . '/'. $file);
                if (!$ans) {
                    $this->logger->error('cannot copy ' . $file . '.changeme file');
                    return 1;
                }
            } else {
                $this->logger->info($dir . '/' . $file . ' file already exists');
            }
        }

        return 0;
    }

    protected function configDb($dir)
    {
        $dbini = $dir . '/db.ini';
        if (preg_match('/XXXXXXX/', file_get_contents($dbini)))
            copy($dbini . '.changeme', $dbini);
        do {
            echo 'host: '; $host = trim(fgets(STDIN));
            echo 'username: '; $username = trim(fgets(STDIN));
            echo 'password: '; $password = trim(fgets(STDIN));
            echo 'dbname: '; $dbname = trim(fgets(STDIN));

            echo PHP_EOL;
            echo 'host:     ' . $host . PHP_EOL;
            echo 'username: ' . $username . PHP_EOL;
            echo 'password: ' . $password . PHP_EOL;
            echo 'dbname:   ' . $dbname . PHP_EOL;
        } while (!UIUtils::confirmPrompt('Are those informations correct?'));
        exec('sed -i \'0,/XXXXXXX/s//' . $host     . '/\' ' . $dbini);
        exec('sed -i \'0,/XXXXXXX/s//' . $username . '/\' ' . $dbini);
        exec('sed -i \'0,/XXXXXXX/s//' . $password . '/\' ' . $dbini);
        exec('sed -i \'0,/XXXXXXX/s//' . $dbname   . '/\' ' . $dbini);
    }

    protected function checkDb($dir)
    {
        require_once($dir . '/bootstrap.php');
        try {
            $db = get_db();
            $tables = $db->fetchAll("SHOW TABLES LIKE '{$db->prefix}options'");
            if (!empty($tables)) {
                $this->logger->error('database not empty');
                return 1;
            }
        } catch (\Exception $e) { }

        return 0;
    }

    protected function configOmeka($form)
    {
        require_once(FORM_DIR . '/Install.php');
        $form = new \Omeka_Form_Install();
        $form->init();
        do {
            echo 'username: '; $username = trim(fgets(STDIN));
            echo 'password: ' . "\x1b[8m"; $password = trim(fgets(STDIN)); echo "\x1b[0m";
            echo 'password_confirm: ' . "\x1b[8m"; $password_confirm = trim(fgets(STDIN)); echo "\x1b[0m";
            echo 'super_email: '; $super_email = trim(fgets(STDIN));
            echo 'site_title: '; $site_title = trim(fgets(STDIN));
            echo 'administrator_email: '; $administrator_email = trim(fgets(STDIN));
            $formIsValid = $form->isValid(array(
                'username' => $username,
                'password' => $password,
                'password_confirm' => $password_confirm,
                'super_email' => $super_email,
                'site_title' => $site_title,
                'administrator_email' => $administrator_email,
                'tag_delimiter' => ',',
                'fullsize_constraint' => '800',
                'thumbnail_constraint' => '200',
                'square_thumbnail_constraint' => '200',
                'per_page_admin' => '10',
                'per_page_public' => '10',
            ));
            if (!$formIsValid) {
                echo 'The following fields are not do not match a condition.' . PHP_EOL;
                $errors = $form->getErrors();
                $errors = array_filter($errors, function($var) { return !empty($var); });
                foreach (array_keys($errors) as $field)
                    echo $field . ': ' . implode(', ', $errors[$field]) . PHP_EOL;
            }
            echo PHP_EOL;
            echo 'username:            ' . $username . PHP_EOL;
            echo 'super_email:         ' . $super_email . PHP_EOL;
            echo 'site_title:          ' . $site_title . PHP_EOL;
            echo 'administrator_email: ' . $administrator_email . PHP_EOL;
        } while (!$formIsValid || !UIUtils::confirmPrompt('Are those informations correct?'));
    }

    protected function installOmeka($form)
    {
        try {
            $installer = new \Installer_Default(get_db());
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            return 1;
        }
        $installer->setForm($form);
        \Zend_Controller_Front::getInstance()->getRouter()->addDefaultRoutes();
        try {
            $installer->install();
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            return 1;
        }

        return 0;
    }
}
