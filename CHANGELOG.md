# Changelog

This project adheres to [Semantic Versioning](http://semver.org/).

### 1.6.0: 06/10/2026

* Reworked from procedural scripts into PHP classes in the `WP_CLI_Sync` namespace, with `core/` and `tasks/` replaced by `includes/`. `Command` registers `wp sync`, `Config` reads the settings and detects the project layout, `Sync` runs the tasks and handles maintenance mode, and `Output` and `Shell` handle the terminal and the commands
* Each task is a class under `includes/tasks/`: `Connection_Check`, `Database_Sync`, `URL_Replace`, `Uploads_Sync`, `Plugins_Sync` and `Plugins_Management`. The uploads and plugins syncs share a `Folder_Sync` base instead of duplicating their rsync handling
* Every SSH connection in a sync shares one, rather than each doing its own handshake. The connection is kept in `~/.ssh` and closed when the sync ends. If `~/.ssh` doesn't exist, each connection is made separately as before
* The two connection checks are combined into one
* The database export is gzipped on the live server and decompressed as it streams in, sending around a fifth of the data. The result shows both sizes, e.g. `Imported 5.2 MB (1.0 MB transferred)`. If the live server has no gzip, or PHP has no zlib, the export is sent uncompressed as before
* The site URL options and the four search-replace passes run in one WP-CLI process instead of six
* Classes are loaded by a small autoloader in `wp-cli-sync.php`, so installs without composer keep working
* Settings are read when `wp sync` runs, rather than being copied into `$_ENV` when the plugin loads
* With `DEV_TASK_DEBUG` set, the connection checks and maintenance mode commands are shown along with the rest
* The code follows the [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/). Run `composer install && vendor/bin/phpcs` to check, with the ruleset in `phpcs.xml.dist`, which also checks compatibility with PHP 7.4 and above
* CI runs PHPCS on every push and pull request
* The plugin header is now a docblock. The release workflow and `install.sh` read the version from either header style, so updating an older install still reports its version

### 1.5.3: 30/09/2026

* The welcome banner is shown in the project's colour, falling back to cyan on terminals without true colour support, and includes the version number
* Pushing a tag publishes a GitHub release, with notes taken from its CHANGELOG.md entry

### 1.5.2: 30/09/2026

* Same code as 1.5.1. The tag was pushed on the wrong commit, and Packagist doesn't allow a published version to change

### 1.5.1: 24/09/2026

* Tidier output: each task gets a heading and a one-line result with its duration, and the sync ends with a total time
* rsync no longer lists every file. A single live progress line is shown instead, followed by a count and size of the files updated
* Database imports show live progress, and `pv` is no longer used
* The URL replacement reports how many replacements were made
* Output from WP-CLI and rsync is only shown when something goes wrong
* Colours and progress lines are skipped when the output isn't a terminal
* `wp sync` now exits with code 1 when a check or task fails, so scripts can detect it

### 1.5.0: 23/09/2026

* `--database` and `--media` flags to sync just the database or just the uploads (and vanilla plugins) folder, plus `--no-database` / `--no-media` to skip either. Media-only syncs skip maintenance mode (thanks @paintface)
* Failed database exports, rsync transfers and remote WP-CLI checks are now reported as errors instead of finishing green (thanks @paintface)
* Post-sync queries are skipped when the database import fails (thanks @paintface)
* rsync paths and exclude patterns are shell-escaped (thanks @paintface)
* Backtick operators replaced with `shell_exec()` for PHP 8.5 compatibility (thanks @paintface)
* Added `wp-cli/entity-command` to the composer requires, fixing the site URL update on composer-managed installs (thanks @paintface)

### 1.4.1: 18/09/2026

* The plugins folder is now synced from the live server on vanilla projects, where composer isn't managing it
* Configurable plugins folder (`PLUGIN_DIR`)

### 1.4.0: 16/09/2026

* Vanilla WordPress support alongside bedrock, with automatic layout detection
* Configurable local and remote WP-CLI binaries (`LOCAL_WP_CLI`, `REMOTE_WP_CLI`)
* Configurable project root (`LOCAL_PROJECT_LOCATION`)
* `UPLOAD_DIR` now defaults to the right path for the detected layout
* Settings can be defined as `wp-config.php` constants, so vanilla projects need no `.env` file
* Site URLs are rewritten after a sync via `LIVE_DOMAIN` / `DEV_DOMAIN`, covering every http, https, www and non-www variant
* Failed connection checks no longer leave the site in maintenance mode

### 1.3.2: 20/10/2023
* Maintanence mode commands to prevent the site being accessed during sync
* ENV Optimisations (merci @gmutschler)
* Custom uploads folder directory support (thanks @paintface)
* Composer stable stability + updated WP-CLI packages
* New folder structure ✨

### 1.3.1: 03/11/2020

* Added welcome and connection success messages
* Added hints to checks

### 1.3.0: 01/11/2020

* Added connection checks
* Improved `.env` variable checks
* Added 'First Sync' instructions to README

### 1.2.1: 15/09/2020

* Fixed compatiblity with `oscarotero/env` 2.0

### 1.2.0: 20/01/2020

* Added `--single-transaction` MySQL flag for non-blocking DB sync
* Added support for rsync excluded directories
* Added support for post-sync database queries to update site settings and such
* Added debug option to show details of commands executed

### 1.1.3: 07/11/2019

* Restored local `wp` command requirement due to incompatibilities with some terminal emulators.

### 1.1.2: 05/11/2019

* DB Sync directory change fix (#1)
* DB Sync now requires `bash`

### 1.1.1: 14/10/2019

* Require Fixes

### 1.1.0: 11/10/2019

* Removed local `wp` command requirement
* Removed typo message
* Composer author details
* Started using Releases

### 1.0.0: 03/04/2019

* Initial Release
