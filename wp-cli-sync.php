<?php
/*
Plugin Name:  WP-CLI Sync
Description:  A WP-CLI command for syncing a live site to a development environment
Version:      1.4.0
Author:       Jon Beaumont-Pike
Author URI:   https://jonbp.co.uk/
License:      MIT License
*/

// Set Default Vars
$env_variables = array(
	'LIVE_SSH_USERNAME' => '',
	'LIVE_SSH_HOSTNAME' => '',
	'LIVE_DOMAIN' => '',
	'REMOTE_PROJECT_LOCATION' => '',
	'DEV_DOMAIN' => '',
	'DEV_ACTIVATED_PLUGINS' => '',
	'DEV_DEACTIVATED_PLUGINS' => '',
	'DEV_POST_SYNC_QUERIES' => '',
	'DEV_SYNC_DIR_EXCLUDES' => '',
	'DEV_TASK_DEBUG' => '',
	// Left empty to be auto-detected in core/variables.php
	'UPLOAD_DIR' => '',
	'LOCAL_PROJECT_LOCATION' => '',
	'LOCAL_WP_CLI' => '',
	'REMOTE_WP_CLI' => ''
);

// Values come from the environment, where bedrock's .env lands them, or from
// constants defined in wp-config.php on a vanilla project
foreach($env_variables as $env_variable => $env_variable_default) {

  $env_variable_value = getenv($env_variable);

  if (empty($env_variable_value) && defined($env_variable)) {
    $env_variable_value = constant($env_variable);
  }

  $_ENV[$env_variable] = $env_variable_value ?: $env_variable_default;

}

// Define Sync Command
if ( defined( 'WP_CLI' ) && WP_CLI ) {
  $sync = function() {

    // Include base functions
    require_once(__DIR__.'/core/functions.php');
    require_once(__DIR__.'/core/variables.php');

    // Include tasks
    require_once(__DIR__.'/tasks/connection_check.php');
    require_once(__DIR__.'/tasks/database_sync.php');
    require_once(__DIR__.'/tasks/url_replace.php');
    require_once(__DIR__.'/tasks/uploads_sync.php');
    require_once(__DIR__.'/tasks/plugins_management.php');

    // Deactivate Maintenance Mode
    $command = $local_wp_cli . ' maintenance-mode deactivate';
    exec($command);

    // Completion Message
    if ($fail_count > 0) {
      task_message('Finished with '.$fail_count. ' errors', 'Warning', 33);
    } else {
      task_message('All Tasks Finished', 'Success', 32);
    }

    // Final Line Break + Color Reset
    lb_cr();

  };

  WP_CLI::add_command('sync', $sync);
}
