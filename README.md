# WP-CLI Sync<a href="https://github.com/jonbp/wp-cli-sync"><img alt="WP-CLI Sync" src="https://jonbp.github.io/project-icons/wp-cli-sync.svg" width="40" height="40" align="right"></a>

[![Packagist Latest Version](https://img.shields.io/packagist/v/jonbp/wp-cli-sync)](https://packagist.org/packages/jonbp/wp-cli-sync)
[![Packagist Downloads](https://img.shields.io/packagist/dm/jonbp/wp-cli-sync)](https://packagist.org/packages/jonbp/wp-cli-sync)
[![GitHub Open Issues](https://img.shields.io/github/issues-raw/jonbp/wp-cli-sync)](https://github.com/jonbp/wp-cli-sync/issues)
[![GitHub Open Pull Requests](https://img.shields.io/github/issues-pr-raw/jonbp/wp-cli-sync)](https://github.com/jonbp/wp-cli-sync/pulls)

## About

A WP-CLI command for syncing a live site to a development environment.

This plugin is designed to be used with a [Roots Bedrock](https://github.com/roots/bedrock) based WordPress project.

![Screenshot](https://i.imgur.com/ugUhcuQ.gif)

## Requirements

You will need the following to use this plugin:

* A [bedrock](https://github.com/roots/bedrock) based WordPress project
* SSH connection to live server
* [WP-CLI](https://github.com/wp-cli/wp-cli)
* [rsync](https://rsync.samba.org)

## Installation

To install this plugin, follow these steps:

1. Require the plugin by running:

```sh
composer require jonbp/wp-cli-sync
```

2. Add the following to your `.env` file (don't forget `.env.example` for reference 😉):

```sh
# WP-CLI Sync Settings [wp sync]
LIVE_SSH_USERNAME=""
LIVE_SSH_HOSTNAME=""
REMOTE_PROJECT_LOCATION="~/gitrepo"

# Plugins should be formatted in a comma seperated format
# For example: "plugin1,plugin2,plugin3"

# Plugins activated on sync
DEV_ACTIVATED_PLUGINS=""

# Plugins deactivated on sync
DEV_DEACTIVATED_PLUGINS=""
```

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

| Variable | Description |
| --- | --- |
| `DEV_POST_SYNC_QUERIES` | A comma seperated list of SQL queries to run after the sync has completed. |
| `DEV_SYNC_DIR_EXCLUDES` | A comma seperated list of directories within the uploads folder to exclude from the sync. |
| `DEV_TASK_DEBUG` | Set to `true` to show debug information about the commands being run. Useful for debugging if something isn't working as expected. |
| `UPLOAD_DIR` | The name of the uploads directory. Defaults to `app/uploads` where the uploads folder is located on a bedrock project. |