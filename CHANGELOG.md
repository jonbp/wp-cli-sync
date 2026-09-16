# Changelog

This project adheres to [Semantic Versioning](http://semver.org/).

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
