<?php

// Fail Count Var
$fail_count = 0;

// Sync vars
$ssh_hostname = $_ENV['LIVE_SSH_HOSTNAME'];
$ssh_username = $_ENV['LIVE_SSH_USERNAME'];
$rem_proj_loc = $_ENV['REMOTE_PROJECT_LOCATION'];
$upload_dir = $_ENV['UPLOAD_DIR'];

// Plugin Vars
$dev_activated_plugins = $_ENV['DEV_ACTIVATED_PLUGINS'];
$dev_deactivated_plugins = $_ENV['DEV_DEACTIVATED_PLUGINS'];

// Move to project root
chdir(ABSPATH.'../../');

// Activate Maintenance Mode
$command = 'vendor/bin/wp maintenance-mode activate';
exec($command);