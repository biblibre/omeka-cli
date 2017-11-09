# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](http://semver.org/spec/v2.0.0.html).

## [1.0.0-alpha.5] - 2017-11-09
## Changed
- Commands plugin-activate and plugin-install are merged into plugin-enable
- Some commands aliases are modified

## [1.0.0-alpha.4] - 2017-10-30
## Added
- New command plugin-search

## Changed
- Command plugin-download requires an exact name for the plugin

## Removed
- OmekaCli\Console\Prompt

## Fixed
- Phar compiler adds shebang
- Prevent warnings if command class does not exist

## [1.0.0-alpha.3] - 2017-10-30
### Changed
- Omeka code is run inside another process to keep the main process clean
- CommandInterface now extends ContextAwareInterface and LoggerAwareInterface
- CommandInterface::run now takes only two parameters
- Test suite sets up a new Omeka installation before running tests

## [1.0.0-alpha.2] - 2017-09-14
### Added
- Global options to adjust verbosity (--quiet, --verbose)
- Command plugin-download learned --force to bypass omeka_minimum_version
- Command plugin-download learned --exclude-github to avoid searching plugins in
  Github repositories

### Changed
- Command check-updates now prints old and new versions
- Command help now prints the command description
- Command install has default values for all its options and do not prompt for
  missing values
- Command install tries to create the database if it does not exist
- Move OmekaCli\Command\PluginCommands\Utils\PluginUtils static methods in
  OmekaCli\Command\PluginCommands\AbstractPluginCommand
- Command plugin-update refuses to update a plugin if it is active
- OmekaDotOrgRepository extract the zip file into a temporary directory first to
  get the plugin's real name
- Command upgrade no longer backup the Omeka directory nor deactivate all
  plugins before upgrading
- Command upgrade compress the database dump with gzip
- Command upgrade no longer ask for automatic recovering if the upgrade fails.
  It is up to the user to manually reinject the database dump if they think it's
  needed
- Namespace renaming
- Command snapshot was split in two (snapshot and snapshot-restore)
- Moved UIUtils to Console\Prompt

### Removed
- Constant OMEKA_CLI_PATH
- Global option --no-prompt
- Command plugin-update forgot --list

### Fixed
- omeka-cli can now be installed using composer

## [1.0.0-alpha.1] - 2017-09-05
### Changed
- installation infos are given as options to the `install` command.
- --no-prompt can be replaced by -n option.
- `install` ask for missing options
- `install`: make help more understandable.
- `install`: also initialize git submodules.

### Fixed
- Bug whan downloads plugins whose name has more than one word.
- Uncaught option parsing exceptions.
- `install` default options.
- administator_email empty with `install` command.
- Typo in `install` command.
- `install`: do not ask for other options if only -v is given

## [0.18.0] - 2017-08-24
### Added
- `plugin-update` command.
- `plugin-download` command.
- `plugin-{,un}install` commands.
- `plugin-{,de}activate` commands.
- `plugin-activate` command.
- Ability to reconfigure the DB when revovering a snapshot.

### Changed
- Add --no-prompt option to help.
- Add --no-prompt option handling to `install` command.
- Add a way to show all Omeka's options to `options` command.

### Fixed
- `install` command.

### Removed
- `plugin *` commands.

## [0.17.0] - 2017-08-08
### Changed
- `install` command installs last tagged version by default.

### Added
- `upgrade` command.
- `snapshot` command.

### Fixed
- Minor fixes.
- `download` command.
- `install` command.

## [0.16.1] - 2017-08-02
### Fixed
- Installation fail when giving two args to `install`.
- Installation fail when database already in use.
- Uncaught exceptions.
- Version in CHANGELOG.md

## [0.16.0] - 2017-08-02
### Added
- `install` command, hellyeah!

### Fixed
- `info` command output.

## [0.15.0] - 2017-07-31
### Added
- `update` command.

### Changed
- plugins backups location.
- rename `update` to `check-updates`.

### Fixed
- GitHub api limitation with `info` command.

## [0.14.0] - 2017-07-27
### Added
- `update` command can update a specific plugin.

### Fixed
- `info` command bloated output.
- Version initialization in `install` command.
- `update` command.
- Plugin prompting.

### Changed
- Abort plugin installation if a dependency is not resolved.
- Many outputs, since omeka-cli know uses a logger.

## [0.13.0] - 2017-07-26
### Changed
- `info` command now does what `upgrade` command did.
- Passing a non-existing command to omeka-cli make it print the usage.

### Removed
- `upgrade` command.

## [0.12.1] - 2017-07-26
### Fixed
- Entry in this files
- Too many calls to getInstance() method.

## [0.12.0] - 2017-07-25
### Added
- --list option to `plugin update` command.

### Changed
- `upgrade` command also shows plugins to update.

## [0.11.0] - 2017-07-25
### Added
- --save option to `plugin update` command.

## [0.10.0] - 2017-07-24
### Removed
- -q option for `plugin` commands.

### Added
- --no-prompt option for omeka-cli.

## [0.9.0] - 2017-07-21
### Added
- `upgrade` command.

## [0.8.0] - 2017-07-18
### Added
- `plugin {,de}activate` commands.

## [0.7.0] - 2017-07-18
### Added
- `plugin {,un}install` commands.
- -q option for the `plugin up` command.

## [0.6.1] - 2017-07-10
### Added
- -q option for the `plugin dl` command.
- Test for `plugin dl` command.
- Big warning in README.md.

### Changed
- Update alias is now 'up' and not 'ud'.
- Many minor code improvements.

## [0.6.0] - 2017-07-06
### Fixed
- Error when choosing 'q' during plugin prompt.

### Added
- `plugin update` command to list plugins that need to be updated.
- Prompt when Omeka version does not fit with plugin version.
- Repository name during plugin selection.

### Changed
- Installation informations in README.md file.

## [0.5.2] - 2017-07-03
### Added
- CHANGELOG.md file.

### Changed
- The way `dl` subcommand search repositories.

### Fixed
- User prompt in `dl` subcommand.
- Typo error.

## [0.5.1] - 2017-06-30
### Fixed
- Error when searching a repo that does not exist.

## [0.5.0] - 2017-06-29
### Added
- `UIUtils` class.
- `plugin dl` command.
- Travis CI tests.

### Changed
- `options` command.
- Argument Parsing.
- UI interface when choosing which plugin to install.

## [0.4.0] - 2017-06-14
### Added
- `options` command.

### Fixed
- Test architecture.

## [0.3.0] - 2017-06-12
### Added
- `info` command.
- Test with PHPUnit.

### Fixed
- Omeka directory checking.

## [0.2.0] - 2016-12-09
### Added
- Ability to downloa plugins from omeka.org.

## 0.1.0 - 2016-12-11

[1.0.0-alpha.5]: https://github.com/biblibre/omeka-cli/compare/v1.0.0-alpha.4...v1.0.0-alpha.5
[1.0.0-alpha.4]: https://github.com/biblibre/omeka-cli/compare/v1.0.0-alpha.3...v1.0.0-alpha.4
[1.0.0-alpha.3]: https://github.com/biblibre/omeka-cli/compare/v1.0.0-alpha.2...v1.0.0-alpha.3
[1.0.0-alpha.2]: https://github.com/biblibre/omeka-cli/compare/v1.0.0-alpha.1...v1.0.0-alpha.2
[1.0.0-alpha.1]: https://github.com/biblibre/omeka-cli/compare/v0.18.0...v1.0.0-alpha.1
[0.18.0]: https://github.com/biblibre/omeka-cli/compare/v0.17.0...v0.18.0
[0.17.0]: https://github.com/biblibre/omeka-cli/compare/v0.16.1...v0.17.0
[0.16.1]: https://github.com/biblibre/omeka-cli/compare/v0.16.0...v0.16.1
[0.16.0]: https://github.com/biblibre/omeka-cli/compare/v0.15.0...v0.16.0
[0.15.0]: https://github.com/biblibre/omeka-cli/compare/v0.14.0...v0.15.0
[0.14.0]: https://github.com/biblibre/omeka-cli/compare/v0.13.0...v0.14.0
[0.13.0]: https://github.com/biblibre/omeka-cli/compare/v0.12.1...v0.13.0
[0.12.1]: https://github.com/biblibre/omeka-cli/compare/v0.12.0...v0.12.1
[0.12.0]: https://github.com/biblibre/omeka-cli/compare/v0.11.0...v0.12.0
[0.11.0]: https://github.com/biblibre/omeka-cli/compare/v0.10.0...v0.11.0
[0.10.0]: https://github.com/biblibre/omeka-cli/compare/v0.9.0...v0.10.0
[0.9.0]: https://github.com/biblibre/omeka-cli/compare/v0.8.0...v0.9.0
[0.8.0]: https://github.com/biblibre/omeka-cli/compare/v0.7.0...v0.8.0
[0.7.0]: https://github.com/biblibre/omeka-cli/compare/v0.6.1...v0.7.0
[0.6.1]: https://github.com/biblibre/omeka-cli/compare/v0.6.0...v0.6.1
[0.6.0]: https://github.com/biblibre/omeka-cli/compare/v0.5.2...v0.6.0
[0.5.2]: https://github.com/biblibre/omeka-cli/compare/v0.5.1...v0.5.2
[0.5.1]: https://github.com/biblibre/omeka-cli/compare/v0.5.0...v0.5.1
[0.5.0]: https://github.com/biblibre/omeka-cli/compare/v0.4.0...v0.5.0
[0.4.0]: https://github.com/biblibre/omeka-cli/compare/v0.3.0...v0.4.0
[0.3.0]: https://github.com/biblibre/omeka-cli/compare/v0.2.0...v0.3.0
[0.2.0]: https://github.com/biblibre/omeka-cli/compare/v0.1.0...v0.2.0
