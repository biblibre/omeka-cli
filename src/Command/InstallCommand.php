<?php

namespace OmekaCli\Command;

use OmekaCli\Application;
use OmekaCli\UIUtils;

class InstallCommand extends AbstractCommand
{
    protected $dbOptions    = array();
    protected $omekaOptions = array();

    public function getDescription()
    {
        return 'install Omeka';
    }

    public function getUsage()
    {
        return 'Usage:' . PHP_EOL
             . '    install [VERSION] DIR [INSTALLATION_ARGS]' . PHP_EOL
             . PHP_EOL
             . 'Arguments' . PHP_EOL
             . '    DIR  the Omeka installation directory' . PHP_EOL
             . '    VERSION  the Omeka version to install' . PHP_EOL
             . PHP_EOL
             . 'Install Omeka. This command needs all the requierements '
             . 'needed to install Omeka the classic way. See omeka.org '
             . 'for more informations.' . PHP_EOL
             . 'When calling this with with the --no-prompt option of '
             . 'omeka-cli, you will have to pass all needed information '
             . 'through the argument INSTALLATION_ARGS. Those argument '
             . 'are in this order:' . PHP_EOL
             . '- database host' . PHP_EOL
             . '- database username' . PHP_EOL
             . '- database user password' . PHP_EOL
             . '- database name' . PHP_EOL
             . '- omeka superuser name' . PHP_EOL
             . '- omeka superuser password' . PHP_EOL
             . '- omeka superuser email' . PHP_EOL
             . '- omeka site title' . PHP_EOL
             . '- omeka admin email' . PHP_EOL
             . PHP_EOL
             . 'EXAMPLE:' . PHP_EOL
             . 'omeka-cli --no-prompt install a_directory localhost '
             . 'dbuser dbP4ssword dbname root omekaP4ssword root@.yee '
             . '\'Hello, Omeka!\' admin@example.yee' . PHP_EOL;

        return $usage;
    }

    public function run($options, $args, Application $application)
    {
        if (NO_PROMPT) {
            $this->omekaOptions['admin_email'] = array_pop($args);
            $this->omekaOptions['site_title']  = array_pop($args);
            $this->omekaOptions['super_email'] = array_pop($args);
            $this->omekaOptions['password']    = array_pop($args);
            $this->omekaOptions['username']    = array_pop($args);
            $this->dbOptions['dbname']   = array_pop($args);
            $this->dbOptions['password'] = array_pop($args);
            $this->dbOptions['username'] = array_pop($args);
            $this->dbOptions['host']     = array_pop($args);
            foreach(array_merge($this->dbOptions, $this->omekaOptions) as $option) {
                if (!$option) {
                    $this->logger->error($this->getUsage());
                    return 1;
                }
            }
        }
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
        if (NO_PROMPT)
            $this->applyDbConfig($dir);
        else
            $this->configDb($dir);

        $this->logger->info('checking the database');
        $cwd = getcwd();
        chdir($dir);
        ob_start();
        $application->initialize();
        ob_end_clean();
        if ($this->checkDb($dir)) {
            $this->logger->error('installation failed');
            return 1;
        }

        $this->logger->info('configuring Omeka');
        if (NO_PROMPT) {
            if (!$form = $this->applyOmekaConfig($dir)) {
                $this->logger->error('something went wrong during Omeka configuration');
                return 1;
            }
        } else {
            $form = $this->configOmeka();
        }

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
        } elseif ((file_exists($dir . '/.git')
              &&   file_exists($dir . '/db.ini.changeme')
              &&   file_exists($dir . '/bootstrap.php'))) {
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

    protected function applyDbConfig($dir)
    {
        $dbini = $dir . '/db.ini';
        if (preg_match('/XXXXXXX/', file_get_contents($dbini)))
            copy($dbini . '.changeme', $dbini);
        exec('sed -i \'0,/XXXXXXX/s//' . $this->dbOptions['host']     . '/\' ' . $dbini);
        exec('sed -i \'0,/XXXXXXX/s//' . $this->dbOptions['username'] . '/\' ' . $dbini);
        exec('sed -i \'0,/XXXXXXX/s//' . $this->dbOptions['password'] . '/\' ' . $dbini);
        exec('sed -i \'0,/XXXXXXX/s//' . $this->dbOptions['dbname']   . '/\' ' . $dbini);
    }

    protected function configDb($dir)
    {
        $dbini = $dir . '/db.ini';
        if (preg_match('/XXXXXXX/', file_get_contents($dbini)))
            copy($dbini . '.changeme', $dbini);
        do {
            fprintf(STDERR, 'host: '); $host = trim(fgets(STDIN));
            fprintf(STDERR, 'username: '); $username = trim(fgets(STDIN));
            fprintf(STDERR, 'password: '); $password = trim(fgets(STDIN));
            fprintf(STDERR, 'dbname: '); $dbname = trim(fgets(STDIN));

            fprintf(STDERR, PHP_EOL);
            fprintf(STDERR, 'host:     ' . $host . PHP_EOL);
            fprintf(STDERR, 'username: ' . $username . PHP_EOL);
            fprintf(STDERR, 'password: ' . $password . PHP_EOL);
            fprintf(STDERR, 'dbname:   ' . $dbname . PHP_EOL);
        } while (!UIUtils::confirmPrompt('Are those informations correct?'));
        exec('sed -i \'0,/XXXXXXX/s//' . $host     . '/\' ' . $dbini);
        exec('sed -i \'0,/XXXXXXX/s//' . $username . '/\' ' . $dbini);
        exec('sed -i \'0,/XXXXXXX/s//' . $password . '/\' ' . $dbini);
        exec('sed -i \'0,/XXXXXXX/s//' . $dbname   . '/\' ' . $dbini);
    }

    protected function checkDb($dir)
    {
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

    protected function applyOmekaConfig()
    {
        require_once(FORM_DIR . '/Install.php');
        $form = new \Omeka_Form_Install();
        $form->init();
        $formIsValid = $form->isValid(array(
        'username'            => $this->omekaOptions['username'],
        'password'            => $this->omekaOptions['password'],
        'password_confirm'    => $this->omekaOptions['password'],
        'super_email'         => $this->omekaOptions['super_email'],
        'site_title'          => $this->omekaOptions['site_title'],
        'administrator_email' => $this->omekaOptions['admin_email'],
        'tag_delimiter'               => ',',
        'fullsize_constraint'         => '800',
        'thumbnail_constraint'        => '200',
        'square_thumbnail_constraint' => '200',
        'per_page_admin'              => '10',
        'per_page_public'             => '10',
        ));
        if (!$formIsValid) {
            fprintf(STDERR, 'The following fields are not do not match a '
                          . 'condition.' . PHP_EOL);
            $errors = $form->getErrors();
            $errors = array_filter($errors, function($var) {
                return !empty($var);
            });
            foreach (array_keys($errors) as $field)
                fprintf(STDERR, $field . ': '
                              . implode(', ', $errors[$field]) . PHP_EOL);
        }

        return $formIsValid ? $form : null;
    }

    protected function configOmeka()
    {
        require_once(FORM_DIR . '/Install.php');
        $form = new \Omeka_Form_Install();
        $form->init();
        do {
            fprintf(STDERR, 'username: '); $username = trim(fgets(STDIN));
            fprintf(STDERR, 'password: ' . "\x1b[8m"); $password = trim(fgets(STDIN)); fprintf(STDERR, "\x1b[0m");
            fprintf(STDERR, 'password_confirm: ' . "\x1b[8m"); $password_confirm = trim(fgets(STDIN)); fprintf(STDERR, "\x1b[0m");
            fprintf(STDERR, 'super_email: '); $super_email = trim(fgets(STDIN));
            fprintf(STDERR, 'site_title: '); $site_title = trim(fgets(STDIN));
            fprintf(STDERR, 'admin_email: '); $admin_email = trim(fgets(STDIN));
            $formIsValid = $form->isValid(array(
                'username' => $username,
                'password' => $password,
                'password_confirm' => $password_confirm,
                'super_email' => $super_email,
                'site_title' => $site_title,
                'admin_email' => $admin_email,
                'tag_delimiter' => ',',
                'fullsize_constraint' => '800',
                'thumbnail_constraint' => '200',
                'square_thumbnail_constraint' => '200',
                'per_page_admin' => '10',
                'per_page_public' => '10',
            ));
            if (!$formIsValid) {
                fprintf(STDERR, 'The following fields are not do not match a condition.' . PHP_EOL);
                $errors = $form->getErrors();
                $errors = array_filter($errors, function($var) { return !empty($var); });
                foreach (array_keys($errors) as $field)
                    fprintf(STDERR, $field . ': ' . implode(', ', $errors[$field]) . PHP_EOL);
            }
            fprintf(STDERR, PHP_EOL);
            fprintf(STDERR, 'username:    ' . $username . PHP_EOL);
            fprintf(STDERR, 'super_email: ' . $super_email . PHP_EOL);
            fprintf(STDERR, 'site_title:  ' . $site_title . PHP_EOL);
            fprintf(STDERR, 'admin_email: ' . $admin_email . PHP_EOL);
        } while (!$formIsValid || !UIUtils::confirmPrompt('Are those informations correct?'));

        return $form;
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
