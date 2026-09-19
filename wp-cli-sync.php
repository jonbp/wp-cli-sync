<?php
/*
Plugin Name:  WP-CLI Sync
Description:  A WP-CLI command for syncing a live site to a development environment
Version:      1.4.1
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
	'PLUGIN_DIR' => '',
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
  $sync = function($args, $assoc_args) {

    // Flags: --database / --media limit the sync; neither = sync everything.
    // filter_var so --database=true/1 and --database=false/0 behave too.
    $database_flag  = isset($assoc_args['database']) ? filter_var($assoc_args['database'], FILTER_VALIDATE_BOOLEAN) : null;
    $media_flag     = isset($assoc_args['media']) ? filter_var($assoc_args['media'], FILTER_VALIDATE_BOOLEAN) : null;
    $only_requested = ($database_flag === true) || ($media_flag === true);
    $sync_database  = $database_flag ?? !$only_requested;
    $sync_media     = $media_flag ?? !$only_requested;

    if (!$sync_database && !$sync_media) {
      WP_CLI::error('Nothing to sync: both --no-database and --no-media given.');
    }

    // Include base functions
    require_once(__DIR__.'/core/functions.php');
    require_once(__DIR__.'/core/variables.php');

    // Activate Maintenance Mode (media-only syncs leave the database alone)
    if ($sync_database) {
      $command = $local_wp_cli . ' maintenance-mode activate';
      exec($command);
    }

    // Include tasks
    require_once(__DIR__.'/tasks/connection_check.php');

    if ($sync_database) {
      require_once(__DIR__.'/tasks/database_sync.php');
      require_once(__DIR__.'/tasks/url_replace.php');
    }

    if ($sync_media) {
      require_once(__DIR__.'/tasks/uploads_sync.php');
      require_once(__DIR__.'/tasks/plugins_sync.php');
    }

    if ($sync_database) {
      require_once(__DIR__.'/tasks/plugins_management.php');

      // Deactivate Maintenance Mode
      $command = $local_wp_cli . ' maintenance-mode deactivate';
      exec($command);
    }

    // Completion Message
    if ($fail_count > 0) {
      task_message('Finished with '.$fail_count. ' errors', 'Warning', 33);
    } else {
      task_message('All Tasks Finished', 'Success', 32);
    }

    // Final Line Break + Color Reset
    lb_cr();

  };

  WP_CLI::add_command('sync', $sync, array(
    'synopsis' => array(
      array('type' => 'flag', 'name' => 'database', 'optional' => true, 'description' => 'Only sync the database'),
      array('type' => 'flag', 'name' => 'media', 'optional' => true, 'description' => 'Only sync the uploads folder'),
    ),
  ));
}
