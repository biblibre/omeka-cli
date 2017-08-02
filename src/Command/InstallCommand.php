<?php

namespace OmekaCli\Command;

use OmekaCli\Application;
use OmekaCli\UIUtils;

use Omeka\Install;
use Omeka\Form;

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
            $ver= $args[2];
        } else if (count($args) != 1) {
            echo $this->getUsage();
            return 1;
        }
        $dir = array_pop($args);

        if (file_exists($dir)) {
            $this->logger->info($dir . ' directory already exists');
        } else {
            $this->logger->info('downloading Omeka');
            if (isset($ver))
                $cmd = 'git clone -b ' . $ver . ' git://github.com/omeka/Omeka ' . $dir;
            else
                $cmd = 'git clone ' . ' git://github.com/omeka/Omeka ' . $dir;
            exec($cmd, $out, $ans);
            if ($ans) {
                $this->logger->error('cannot download Omeka');
                return 1;
            }
        }

        $this->logger->info('copying changeme files');
        $files = array(
            'db.ini',
            '.htaccess',
            'application/tests/config.ini',
            'application/tests/config.ini',
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
                $this->logger->info($dir . $file . ' file already exists');
            }
        }

        $this->logger->info('configuring database');
        if (preg_match('/XXXXXXX/', file_get_contents($dir . '/db.ini'))) {
            $ans = copy($dir . '/db.ini.changeme',
                        $dir . '/db.ini');
            $this->configDb($dir . '/db.ini');
        }

        chdir($dir);
        ob_start();
        $application->initialize();
        ob_end_clean();
        $this->logger->info('configuring Omeka');
        $form = new \Omeka_Form_Install();
        $form->init();
        $this->configOmeka($form);

        $this->logger->info('installing Omeka');
        $installer = new \Installer_Default(get_db());
        $installer->setForm($form);
        \Zend_Controller_Front::getInstance()->getRouter()->addDefaultRoutes();
        $installer->install();

        return 0;
    }

    protected function configDb($dbini)
    {
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

    protected function configOmeka($form)
    {
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
}
