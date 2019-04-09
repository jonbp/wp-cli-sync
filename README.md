# WP CLI Sync

A WP-CLI command for syncing a live site to a development environment

## Requirements

* A [bedrock](https://github.com/wp-cli/wp-cli) based WordPress project
* [WP-CLI](https://github.com/wp-cli/wp-cli)
* [rsync](https://rsync.samba.org)

## Installation

1. Add the following to the `repositories` area of your project's `composer.json`:

```json
{
  "type": "vcs",
  "url": "https://github.com/jonbp/wp-cli-sync.git"
}
```

2. Require the plugin by running:

```sh
composer require jonbp/wp-cli-sync:dev-master
```

3. Add the following to your `.env` file (don't forget `.env.example` for reference 😉):

```sh
# WP-CLI Sync Settings [wp sync]
LIVE_SSH_HOSTNAME=""
LIVE_SSH_USERNAME=""
REMOTE_PROJECT_LCOATION="~/gitrepo"

# Plugins should be formatted in a comma seperated format
# For example: "plugin1,plugin2,plugin3"

# Plugins activated on sync
DEV_ACTIVATED_PLUGINS=""

# Plugins deactivated on sync
DEV_DEACTIVATED_PLUGINS=""
```

4. Run

```sh
wp sync
```