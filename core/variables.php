<?php

// Fail Count Var
$fail_count = 0;

// Sync vars
$ssh_hostname = $_ENV['LIVE_SSH_HOSTNAME'];
$ssh_username = $_ENV['LIVE_SSH_USERNAME'];
$rem_proj_loc = $_ENV['REMOTE_PROJECT_LOCATION'];
$upload_dir = $_ENV['UPLOAD_DIR'];
$plugin_dir = $_ENV['PLUGIN_DIR'];

// Plugin Vars
$dev_activated_plugins = $_ENV['DEV_ACTIVATED_PLUGINS'];
$dev_deactivated_plugins = $_ENV['DEV_DEACTIVATED_PLUGINS'];

// Live and dev domains. Any scheme and trailing slash is stripped so the URLs
// can be rebuilt, and the dev domain falls back to a dev. subdomain.
$live_domain = rtrim(preg_replace('#^https?://#', '', $_ENV['LIVE_DOMAIN']), '/');
$dev_domain = rtrim($_ENV['DEV_DOMAIN'] ?: ($live_domain ? 'dev.'.$live_domain : ''), '/');
$dev_url = ($dev_domain && !preg_match('#^https?://#', $dev_domain)) ? 'http://'.$dev_domain : $dev_domain;

// Local project root. Bedrock keeps WordPress in web/wp, so the root sits two
// levels above ABSPATH. A classic install has its root at ABSPATH.
$bedrock_root = realpath(ABSPATH.'../../');
$is_bedrock = ($bedrock_root && file_exists($bedrock_root.'/web/wp-config.php'));

$loc_proj_loc = $_ENV['LOCAL_PROJECT_LOCATION'] ?: ($is_bedrock ? $bedrock_root : rtrim(ABSPATH, '/'));

// Local WP-CLI binary, relative to the project root or an absolute path
$local_wp_cli = $_ENV['LOCAL_WP_CLI'];

if (empty($local_wp_cli)) {
  $local_wp_cli = file_exists($loc_proj_loc.'/vendor/bin/wp') ? 'vendor/bin/wp' : 'wp';
}

// Remote WP-CLI binary. Relative paths are resolved against the remote project
// location, absolute and home-relative paths are used as given.
$remote_wp_cli = $_ENV['REMOTE_WP_CLI'] ?: 'vendor/bin/wp';

if (($remote_wp_cli[0] != '/') && ($remote_wp_cli[0] != '~')) {
  $remote_wp_cli = $rem_proj_loc.'/'.$remote_wp_cli;
}

// Uploads folder, relative to the project root
if (empty($upload_dir)) {
  $upload_dir = $is_bedrock ? 'web/app/uploads' : 'wp-content/uploads';
}

// Plugins folder, relative to the project root. Only used on a vanilla project,
// as bedrock keeps its plugins under composer's control
if (empty($plugin_dir)) {
  $plugin_dir = 'wp-content/plugins';
}

// Move to project root
chdir($loc_proj_loc);
