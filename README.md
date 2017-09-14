# Omeka CLI

Command line tool for [Omeka]

This tool allows to interact with Omeka by using a command line interface.
It also provides everything needed for Omeka plugins to create custom
commands.

## Usage

    omeka-cli [-h | --help]
    omeka-cli [-C <omeka-path>] [<options>...] COMMAND [ARGS...]

## Available commands

### General commands

    check-updates  check for updates
    help           print help for a specific command
    info           print informations about current Omeka installation
    install        install Omeka
    list           list available commands
    options        edit and see the "omeka_options" table
    snapshot       create or recover a snapshot
    upgrade        upgrade Omeka
    version        print version of omeka-cli

### Plugin related commands

    plugin-activate    activate a plugin
    plugin-deactivate  deactivate a plugin
    plugin-install     install a plugin
    plugin-uninstall   uninstall a plugin
    plugin-downloads   downloads a plugin

## Requirements

- PHP (>= 5.6)
- git

## Installation

### Using composer

```sh
# For the latest released version
composer global require biblibre/omeka-cli:@alpha   # No stable releases yet!

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
bin/omeka-cli version
```

## Creating custom commands

To create a custom command named `bar` with the Foo plugin, put the
following code in the `initialize` hook of your plugin's main class:

```php
$events = Zend_EventManager_StaticEventManager::getInstance();
$events->attach('OmekaCli', 'commands', function() {
    return array(
        'Foo:Bar' => array(
            'class' => 'Foo_Bar',
            'aliases' => array('bar'),
        ),
    );
});
```

and define a class `Foo_Bar` which implements
[OmekaCli\Command\CommandInterface](src/Command/CommandInterface.php)

Then you will be able to run the command either this way:

```sh
omeka-cli Foo:Bar [OPTION...] [ARG...]
```

or using the alias:

```sh
omeka-cli bar [OPTION...] [ARG...]
```

To see how to create a custom command from a plugin in practice, see plugin
[Foo].

## Running tests

**Do not run tests on your own Omeka installation!**

If you want to test `omeka-cli`, run:

```sh
composer install --dev
OMEKA_PATH=/path/to/omeka vendor/bin/phpunit -c tests/phpunit.xml
```

The environment variable `OMEKA_PATH` must be defined to run the tests.

## License

GPL 3.0+

[Omeka]: https://omeka.org/
[Releases]: https://github.com/biblibre/omeka-cli/releases
[Foo]: https://github.com/biblibre/omeka-plugin-Foo
