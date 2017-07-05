# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](http://semver.org/spec/v2.0.0.html).

## [Unreleased]
### Fixed
- Error when choosing 'q' during plugin prompt.

### Added
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
