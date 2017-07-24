# Omeka CLI

Command line tool for [Omeka](http://omeka.org/)

This tool allows to interact with Omeka by using a command line interface.
It also provides everything needed for Omeka plugins to create custom commands.

## Usage

    omeka-cli {-h|--help}
    omeka-cli [-C <omeka-path>] [--no-prompt] COMMAND [ARGS...]

## Available commands

    help     print help for a specific command
    info     print informations about current Omeka installation
    list     list available commands
    options  edit and see the "omeka_options" table
    plugin   manage plugins
    upgrade  check omeka-cli and Omeka version
    version  print version of omeka-cli

## Installation

**NOTE** You will need `git` to use omeka-cli.

    $ git clone https://github.com/biblibre/omeka-cli.git
    $ cd omeka-cli
    $ composer install --no-dev
    $ bin/omeka-cli version

## Creating custom commands

To create a custom command, put the following code in the `initialize` hook of
your plugin's main class:

```php
$events = Zend_EventManager_StaticEventManager::getInstance();
$events->attach('OmekaCli', 'commands', function() {
    return array(
        'myplugin:mycommand' => array(
            'class' => 'MyPlugin_MyCommand',
            'aliases' => array('mycommand'),
        ),
    );
});
```

and define a class `MyPlugin_MyCommand` which implements
[OmekaCli\Command\CommandInterface](src/Command/CommandInterface.php)

Then you will be able to run

    $ omeka-cli myplugin:mycommand [OPTION...] [ARG...]

or, using the alias,

    $ omeka-cli mycommand [OPTION...] [ARG...]

## Running tests

**Do not run tests on your own Omeka installation!**

If you want to test `omeka-cli`, run:

    $ OMEKA_PATH=<path_to_omeka> vendor/bin/phpunit -c tests/phpunit.xml 

The environment variable `OMEKA_PATH` must be defined to run the tests.

## License

GPL 3.0+
