# Omeka CLI

Command line tool for [Omeka][omeka]

This tool allows to interact with Omeka by using a command line interface.
It also provides everything needed for Omeka plugins to create custom
commands.

## Usage

    omeka-cli {-h|--help}
    omeka-cli [-C <omeka-path>] [--no-prompt] COMMAND [ARGS...]

## Available commands

    check-updates  check for updates
    help           print help for a specific command
    info           print informations about current Omeka installation
    install        install Omeka
    list           list available commands
    options        edit and see the "omeka_options" table
    plugin         manage plugins
    snapshot       create or recover a snapshot
    upgrade        upgrade Omeka
    version        print version of omeka-cli

## Installation

**NOTE** You will need `git` to use omeka-cli.

    $ git clone https://github.com/biblibre/omeka-cli.git
    $ cd omeka-cli
    $ composer install --no-dev
    $ bin/omeka-cli version

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

and define a class `Foo_Bar` which extends
[OmekaCli\Command\AbstractCommands](src/Command/AbstractCommands.php)

Then you will be able to run the command either this way:

    $ omeka-cli Foo:Bar [OPTION...] [ARG...]

or using the alias:

    $ omeka-cli bar [OPTION...] [ARG...]

Read this [example][example] to see how to create a custom command from a
plugin in practice.

## Running tests

**Do not run tests on your own Omeka installation!**

If you want to test `omeka-cli`, run:

    $ OMEKA_PATH=<path_to_omeka> vendor/bin/phpunit -c tests/phpunit.xml

The environment variable `OMEKA_PATH` must be defined to run the tests.

## License

GPL 3.0+

[example]: https://github.com/biblibre/omeka-plugin-Foo
[omeka]:   http://omeka.org/
