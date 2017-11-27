# Omeka CLI

Command line tool for [Omeka]

This tool allows to interact with Omeka by using a command line interface.
It also provides everything needed for Omeka plugins to create custom
commands.

## Usage

    omeka-cli [-h | --help]
    omeka-cli [-V | --version]
    omeka-cli <command> [options] [arguments]

## Available commands

    check-updates     check for updates
    help              print help for a specific command
    status            print status of current Omeka installation
    install           install Omeka
    list              list available commands
    options           list, get and set Omeka options
    plugin-disable    disable a plugin
    plugin-download   downloads a plugin
    plugin-enable     enable a plugin (install & activate)
    plugin-list       list all plugins
    plugin-search     search a plugin
    plugin-uninstall  uninstall a plugin
    plugin-update     update a plugin
    snapshot          create a snapshot
    snapshot-restore  restore a snapshot
    upgrade           upgrade Omeka

## Requirements

- PHP (>= 5.6)
- git

## Installation

### Using composer

```sh
# For the latest released version
composer global require biblibre/omeka-cli:@beta   # No stable releases yet!

# For the latest dev version
composer global require biblibre/omeka-cli:@dev
```

Then add `~/.config/composer/vendor/bin` to your `PATH`

```sh
export PATH=~/.config/composer/vendor/bin:$PATH
```

### Using the phar

Download the latest ̀`omeka-cli.phar` from [Releases] page.

```sh
wget https://github.com/biblibre/omeka-cli/releases/download/$VERSION/omeka-cli.phar
chmod +x omeka-cli.phar
sudo mv omeka-cli.phar /usr/local/bin/omeka-cli
```

### Using the sources

```sh
git clone https://github.com/biblibre/omeka-cli.git
cd omeka-cli
composer install --no-dev
bin/omeka-cli --version
```

## Creating custom commands

To create a custom command named `foo:bar` with the Foo plugin, put the
following code in the `initialize` hook of your plugin's main class:

```php
$events = Zend_EventManager_StaticEventManager::getInstance();
$events->attach('OmekaCli', 'commands', function() {
    return array(
        'Foo_Bar',
    );
});
```

and define a class `Foo_Bar` which extends
[OmekaCli\Command\AbstractCommand](src/Command/AbstractCommand.php)

You will have to implements at least `configure` and `execute` methods.

For instance:

```php
use OmekaCli\Command\AbstractCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Foo_Bar extends AbstractCommand
{
    protected function configure()
    {
        $this->setName('foo:bar');
        $this->setDescription('print something to stdout');
        $this->setAliases(array('bar'));
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Hello, omeka-cli!');

        return 0;
    }
}
```

Then you will be able to run the command either this way:

```sh
omeka-cli foo:bar [OPTION...] [ARG...]
```

or using the alias:

```sh
omeka-cli bar [OPTION...] [ARG...]
```

To see how to create a custom command from a plugin in practice, see plugin
[Foo].

## Running tests

If you want to test `omeka-cli`, copy `phpunit.xml.dist` into `phpunit.xml` and
change environment variables `OMEKA_DB_*` as needed.
If you want to avoid downloading Omeka before every run, you can download the
ZIP file manually, and put its local path in `OMEKA_ZIP_PATH`.

After you've done that, run

```sh
composer install
vendor/bin/phpunit
```

## License

GPL 3.0+

[Omeka]: https://omeka.org/
[Releases]: https://github.com/biblibre/omeka-cli/releases
[Foo]: https://github.com/biblibre/omeka-plugin-Foo
