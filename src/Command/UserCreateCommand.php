<?php

namespace OmekaCli\Command;

use OmekaCli\Sandbox\OmekaSandbox;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use User;

class UserCreateCommand extends AbstractCommand
{
    public function getSynopsis($short = false)
    {
        return sprintf('%s <username> <email> <password> <role>', $this->getName());
    }

    protected function configure()
    {
        $this->setName('user-create');
        $this->setDescription('create user');

        $this->addOption('role', InputOption::VALUE_REQUIRED, 'User role (super, admin, researcher, contributor)', 'contributor');
        $this->addArgument('username', InputArgument::REQUIRED, 'Username');
        $this->addArgument('email', InputArgument::REQUIRED, 'E-mail address');
        $this->addArgument('password', InputArgument::REQUIRED, 'Password');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $args = $input->getArguments();
        $stderr = $this->getStderr();

        $username = $input->getArgument('username');
        $email = $input->getArgument('email');
        $password = $input->getArgument('password');
        $role = $input->getOption('role');

        $sandbox = $this->getSandbox();
        $sandbox->execute(function () use ($username, $email, $password, $role, $stderr) {
            $user = new User();

            $user->username = $username;
            $user->name = $username;
            $user->email = $email;
            $user->role = $role;
            $user->active = 1;
            $user->setPassword($password);

            if ($user->save(false)) {
                $stderr->writeln(sprintf('User %s created successfully', $username));
            } else {
                $stderr->writeln(sprintf('User %s not created:', $username));
                foreach ($user->getErrors()->get() as $error) {
                    $stderr->writeln($error);
                }
            }
        }, OmekaSandbox::ENV_SHORTLIVED);

        return 0;
    }
}
