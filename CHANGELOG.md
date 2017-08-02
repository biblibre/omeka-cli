# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](http://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.15.0] - 2017-08-02
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

[Unreleased]: https://github.com/biblibre/omeka-cli/compare/v0.5.1...HEAD
[0.5.1]: https://github.com/biblibre/omeka-cli/compare/v0.5.0...v0.5.1
[0.5.0]: https://github.com/biblibre/omeka-cli/compare/v0.4.0...v0.5.0
[0.4.0]: https://github.com/biblibre/omeka-cli/compare/v0.3.0...v0.4.0
[0.3.0]: https://github.com/biblibre/omeka-cli/compare/v0.2.0...v0.3.0
[0.2.0]: https://github.com/biblibre/omeka-cli/compare/v0.1.0...v0.2.0
