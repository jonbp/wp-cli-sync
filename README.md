# WP-CLI Sync<a href="https://github.com/jonbp/wp-cli-sync"><img alt="WP-CLI Sync" src="https://jonbp.github.io/project-icons/wp-cli-sync.svg" width="40" height="40" align="right"></a>

[![Packagist Latest Version](https://img.shields.io/packagist/v/jonbp/wp-cli-sync)](https://packagist.org/packages/jonbp/wp-cli-sync)
[![Packagist Downloads](https://img.shields.io/packagist/dm/jonbp/wp-cli-sync)](https://packagist.org/packages/jonbp/wp-cli-sync)
[![GitHub Open Issues](https://img.shields.io/github/issues-raw/jonbp/wp-cli-sync)](https://github.com/jonbp/wp-cli-sync/issues)
[![GitHub Open Pull Requests](https://img.shields.io/github/issues-pr-raw/jonbp/wp-cli-sync)](https://github.com/jonbp/wp-cli-sync/pulls)

## About

A WP-CLI command for syncing a live site to a development environment.

This plugin works with both [Roots Bedrock](https://github.com/roots/bedrock) projects and vanilla WordPress installations. The project layout, uploads folder and WP-CLI binary are detected automatically, and can be overridden if needed.

![Screenshot](https://i.imgur.com/ugUhcuQ.gif)

## Requirements

Locally:

* A WordPress project, either [bedrock](https://github.com/roots/bedrock) based or vanilla
* [WP-CLI](https://github.com/wp-cli/wp-cli)
* [rsync](https://rsync.samba.org)

On the live server:

* An SSH connection you can use without a password prompt
* [WP-CLI](https://github.com/wp-cli/wp-cli), pointed at by `REMOTE_WP_CLI` if it isn't at `vendor/bin/wp`
* [rsync](https://rsync.samba.org)

The plugin itself only ever runs locally, so there's nothing to install on the live server.

## Installation

To install this plugin, follow these steps:

1. Require the plugin by running:

```sh
composer require jonbp/wp-cli-sync
```

On a vanilla project without composer, drop the plugin into `wp-content/mu-plugins/wp-cli-sync/` and load it with a `wp-content/mu-plugins/wp-cli-sync-loader.php` file containing:

```php
<?php
require_once __DIR__ . '/wp-cli-sync/wp-cli-sync.php';
```

2. On a bedrock project, add the following to your `.env` file (don't forget `.env.example` for reference 😉):

```sh
# WP-CLI Sync Settings [wp sync]
LIVE_SSH_USERNAME=""
LIVE_SSH_HOSTNAME=""
LIVE_DOMAIN=""
REMOTE_PROJECT_LOCATION="~/gitrepo"

# Plugins should be formatted in a comma seperated format
# For example: "plugin1,plugin2,plugin3"

# Plugins activated on sync
DEV_ACTIVATED_PLUGINS=""

# Plugins deactivated on sync
DEV_DEACTIVATED_PLUGINS=""
```

On a vanilla project there's no `.env` file to read, so define the same names as constants in `wp-config.php` instead, above the `wp-settings.php` require:

```php
/* WP-CLI Sync Settings [wp sync] */
define( 'LIVE_SSH_USERNAME', '' );
define( 'LIVE_SSH_HOSTNAME', '' );
define( 'LIVE_DOMAIN', '' );
define( 'REMOTE_PROJECT_LOCATION', '~/gitrepo' );

/* Plugins activated / deactivated on sync, comma seperated */
define( 'DEV_ACTIVATED_PLUGINS', '' );
define( 'DEV_DEACTIVATED_PLUGINS', '' );
```

Every variable in this README works either way. The environment is checked first, so an inline `DEV_TASK_DEBUG=true wp sync` still overrides whatever `wp-config.php` sets.

`LIVE_DOMAIN` is the live site's bare domain, such as `example.com`. Once it's set, the sync rewrites the database to `dev.example.com` afterwards, replacing every `http`, `https`, `www` and non-`www` variant of the live URL. Leave it empty to skip that step.

3. Run `wp sync` from the project root.

## First Sync

You may find yourself working on a bedrock project that already exists on a production server and you don't have the database setup locally yet. Running `wp sync` in the project will fail in this case as it requires an active WordPress installation to run.

To remedy this, you can run the following commands to create a database (if necessary) and create a basic installation inside that database in order to run the plugin and its first sync.

```
wp db create
wp core install --url=abc.xyz --title=abc --admin_user=abc --admin_password=abc --admin_email=abc@abc.xyz --skip-email
```

It’s not necessary to edit the variables on the second line as the database is overwritten by the plugin during sync. The code is simply to give the plugin the requirements it needs to run without the real database installed.

## Extra Environment Variables

Below is a list of extra environment variables that can be added to your `.env` file to customise the sync process.

These can be set in your `.env` file or, on a vanilla project, as `wp-config.php` constants.

| Variable | Description |
| --- | --- |
| `DEV_DOMAIN` | The domain the synced database is rewritten to. Defaults to the live domain with a `dev.` prefix. Include a scheme to use `https` locally, otherwise `http` is assumed. |
| `DEV_POST_SYNC_QUERIES` | A comma seperated list of SQL queries to run after the sync has completed. |
| `DEV_SYNC_DIR_EXCLUDES` | A comma seperated list of directories within the uploads folder to exclude from the sync. |
| `DEV_TASK_DEBUG` | Set to `true` to show debug information about the commands being run. Useful for debugging if something isn't working as expected. |
| `LOCAL_PROJECT_LOCATION` | The path to the local project root. Detected automatically from the WordPress layout. |
| `LOCAL_WP_CLI` | The local WP-CLI binary, relative to the project root or an absolute path. Defaults to `vendor/bin/wp` when present, otherwise `wp`. |
| `REMOTE_WP_CLI` | The live server's WP-CLI binary. Relative paths are resolved against `REMOTE_PROJECT_LOCATION`, absolute and `~/` paths are used as given. Defaults to `vendor/bin/wp`. |
| `UPLOAD_DIR` | The uploads directory, relative to the project root. Defaults to `web/app/uploads` on bedrock and `wp-content/uploads` on a vanilla project. |