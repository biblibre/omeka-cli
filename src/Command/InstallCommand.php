<?php

namespace OmekaCli\Command;

use OmekaCli\Application;
use OmekaCli\UIUtils;

use GetOptionKit\OptionCollection;

class InstallCommand extends AbstractCommand
{
    protected $options = array();

    public function getOptionsSpec()
    {
        $cmdSpec = new OptionCollection;
        $cmdSpec->add('v|version:',             'Omeka version')
                ->isa('String');
        $cmdSpec->add('h|db-host?',             'database host')
                ->isa('String');
        $cmdSpec->add('u|db-user?',             'database user name')
                ->isa('String');
        $cmdSpec->add('p|db-pass?',             'database user password')
                ->isa('String');
        $cmdSpec->add('n|db-name:',             'database name')
                ->isa('String');
        $cmdSpec->add('U|omeka-user-name:',     'Omeka superuser name')
                ->isa('String');
        $cmdSpec->add('P|omeka-user-password:', 'Omeka superuser password')
                ->isa('String');
        $cmdSpec->add('E|omeka-user-email:',    'Omeka superuser email')
                ->isa('String');
        $cmdSpec->add('T|omeka-site-title?',    'Omeka site title')
                ->isa('String');
        $cmdSpec->add('A|omeka-admin-email:',   'Omeka admin email')
                ->isa('String');

        return $cmdSpec;
    }

    public function getDescription()
    {
        return 'install Omeka';
    }

    public function getUsage()
    {
        return 'Usage:' . PHP_EOL
             . '    install [OPTIONS] DIR' . PHP_EOL
             . PHP_EOL
             . 'Arguments' . PHP_EOL
             . '    DIR  the Omeka installation directory' . PHP_EOL
             . PHP_EOL
             . 'Install Omeka. This command needs all the requirements '
             . 'needed to install Omeka the classic way. See omeka.org '
             . 'for more informations.' . PHP_EOL
             . PHP_EOL
             . 'OPTIONS:' . PHP_EOL
             . '-v, --version TAG' . PHP_EOL
             . '    git tag refering to an Omeka version' . PHP_EOL
             . '-h, --db-host DB_HOST' . PHP_EOL
             . '    database host, default: \'localhost\'' . PHP_EOL
             . '-u, --db-user DB_USER' . PHP_EOL
             . '    database user name, default: \'root\'' . PHP_EOL
             . '-p, --db-pass DB_PASS' . PHP_EOL
             . '    database user password, default: \'\'' . PHP_EOL
             . '-n, --db-name DB_NAME' . PHP_EOL
             . '    database name' . PHP_EOL
             . '-U, --omeka-user-name OMEKA_USER_NAME' . PHP_EOL
             . '    Omeka superuser name' . PHP_EOL
             . '-P, --omeka-user-password OMEKA_USER_PASSWORD' . PHP_EOL
             . '    Omeka superuser password' . PHP_EOL
             . '-E, --omeka-user-email OMEKA_USER_EMAIL' . PHP_EOL
             . '    Omeka superuser email' . PHP_EOL
             . '-T, --omeka-site-title OMEKA_SITE_TITLE' . PHP_EOL
             . '    Omeka site title, default: \'Hello, Omeka!\'' . PHP_EOL
             . '-A, --omeka-admin-email OMEKA_ADMIN_EMAIL' . PHP_EOL
             . '    Omeka admin email' . PHP_EOL
             . 'All options, except -v, should be given. Otherwise, '
             . 'omeka-cli will either prompt the user or use default the'
             . ' value.' . PHP_EOL;
    }

    public function run($options, $args, Application $application)
    {
        if (count($args) != 1) {
            echo $this->getUsage();
            return 1;
        }

        $dir = array_pop($args);
        $ver = null;
        if (array_key_exists('version', $options)) {
            if ($options['version'][0] != 'v')
                $ver = 'v' . $options['version'];
            else
                $ver = $options['version'];
            unset($options['version']);
        }
        if (!empty($options)) {
            if (!array_key_exists('db-host', $options))
                $options['db-host'] = 'localhost';
            if (!array_key_exists('db-user', $options))
                $options['db-user'] = 'root';
            if (!array_key_exists('db-pass', $options))
                $options['db-pass'] = null;
            if (!array_key_exists('omeka-site-title', $options))
                $options['omeka-site-title'] = 'Hello, Omeka!';
            if (count($options) != 9) {
                if (NO_PROMPT) {
                    $this->logger->error('missing options');
                    return 1;
                }
                if (!array_key_exists('db-name', $options)) {
                    echo 'Database name?' . PHP_EOL;
                    $options['db-host'] = trim(fgets(STDIN));
                }
                if (!array_key_exists('omeka-user-name', $options)) {
                    echo 'Omeka user name?' . PHP_EOL;
                    $options['omeka-user-name'] = trim(fgets(STDIN));
                }
                if (!array_key_exists('omeka-user-password', $options)) {
                    echo 'Omeka user password?' . PHP_EOL;
                    $options['omeka-user-password'] = trim(fgets(STDIN));
                }
                if (!array_key_exists('omeka-user-email', $options)) {
                    echo 'Omeka user email?' . PHP_EOL;
                    $options['omeka-user-email'] = trim(fgets(STDIN));
                }
                if (!array_key_exists('omeka-site-title', $options)) {
                    echo 'Omeka site title?' . PHP_EOL;
                    $options['omeka-site-title'] = trim(fgets(STDIN));
                }
                if (!array_key_exists('omeka-admin-email', $options)) {
                    echo 'Omeka admin email?' . PHP_EOL;
                    $options['omeka-admin-email'] = trim(fgets(STDIN));
                }
            }
            $this->options = $options;
        }

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
        if (empty($this->options))
            $this->configDb($dir);
        else
            $this->applyDbConfig($dir);

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
        if (empty($this->options)) {
            $form = $this->configOmeka();
        } else {
            if (!$form = $this->applyOmekaConfig($dir)) {
                $this->logger->error('something went wrong during Omeka configuration');
                return 1;
            }
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
        exec('sed -i \'0,/XXXXXXX/s//' . $this->options['db-host']     . '/\' ' . $dbini);
        exec('sed -i \'0,/XXXXXXX/s//' . $this->options['db-user'] . '/\' ' . $dbini);
        exec('sed -i \'0,/XXXXXXX/s//' . $this->options['db-pass'] . '/\' ' . $dbini);
        exec('sed -i \'0,/XXXXXXX/s//' . $this->options['db-name']   . '/\' ' . $dbini);
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
        'username'            => $this->options['omeka-user-name'],
        'password'            => $this->options['omeka-user-password'],
        'password_confirm'    => $this->options['omeka-user-password'],
        'super_email'         => $this->options['omeka-user-email'],
        'site_title'          => $this->options['omeka-site-title'],
        'administrator_email' => $this->options['omeka-admin-email'],
        'tag_delimiter'               => ',',
        'fullsize_constraint'         => '800',
        'thumbnail_constraint'        => '200',
        'square_thumbnail_constraint' => '200',
        'per_page_admin'              => '10',
        'per_page_public'             => '10',
        ));
        if (!$formIsValid) {
            fprintf(STDERR, 'The following fields do not match a condition.' . PHP_EOL);
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
                'administrator_email' => $admin_email,
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
