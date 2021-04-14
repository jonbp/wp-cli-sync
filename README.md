<h1 align="center">
  <a href="https://github.com/jonbp/wp-cli-sync"><img alt="WP-CLI Sync" src="https://jonbp.github.io/project-icons/wp-cli-sync.svg" width="64" height="64"></a><br />WP-CLI Sync
</h1>

<p align="center">
  <a href="https://packagist.org/packages/jonbp/wp-cli-sync">
    <img alt="Packagist Latest Version" src="https://img.shields.io/packagist/v/jonbp/wp-cli-sync" />
  </a>

  <a href="https://packagist.org/packages/jonbp/wp-cli-sync">
    <img alt="Packagist Downloads" src="https://img.shields.io/packagist/dm/jonbp/wp-cli-sync" />
  </a>

  <a href="https://github.com/jonbp/wp-cli-sync/issues">
    <img alt="GitHub Open Issues" src="https://img.shields.io/github/issues-raw/jonbp/wp-cli-sync" />
  </a>

  <a href="https://github.com/jonbp/wp-cli-sync/pulls">
    <img alt="GitHub Open Pull Requests" src="https://img.shields.io/github/issues-pr-raw/jonbp/wp-cli-sync" />
  </a>
</p>

<p align="center">A WP-CLI command for syncing a live site to a development environment</p>

<p align="center">
  <img src="https://i.imgur.com/ugUhcuQ.gif" />
</p>


## Requirements

* A [bedrock](https://github.com/roots/bedrock) based WordPress project
* SSH connection to live server
* [WP-CLI](https://github.com/wp-cli/wp-cli)
* [rsync](https://rsync.samba.org)

## Installation

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

# Dirs to exclude from sync
# Multiple dirs can be provided by separating with a comma
# Use dir names or paths relative to uploads dir
DEV_SYNC_DIR_EXCLUDES=""

# DB Queries to run after sync
DEV_POST_SYNC_QUERIES=""

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