<?php
/*
Plugin Name:  WP-CLI Sync
Description:  A WP-CLI command for syncing a live site to a development environment
Version:      1.2.2
Author:       Jon Beaumont-Pike
Author URI:   https://jonbp.co.uk/
License:      MIT License
*/

// Set Default Vars
$env_variables = array(
  'LIVE_SSH_HOSTNAME',
  'LIVE_SSH_USERNAME',
  'REMOTE_PROJECT_LOCATION',
  'DEV_ACTIVATED_PLUGINS',
  'DEV_DEACTIVATED_PLUGINS',
  'DEV_POST_SYNC_QUERIES',
  'DEV_SYNC_DIR_EXCLUDES',
  'DEV_TASK_DEBUG'
);

foreach($env_variables as $env_variable) {
  $_ENV[$env_variable] = isset($_ENV[$env_variable]) ? $_ENV[$env_variable]:'';
}

// Define Sync Command
if ( defined( 'WP_CLI' ) && WP_CLI ) {
  $sync = function() {

    // Task Message
    function task_message($message, $title='Task', $color = 34, $firstBreak = true) {
      if($firstBreak == true) {
        echo "\n";
      }
      echo "\033[".$color."m".$title.": ".$message."\n\033[0m";
    }

    // Debug Message
    function debug_message($message, $title='Debug', $color = 33, $firstBreak = false) {
      if (empty($_ENV['DEV_TASK_DEBUG'])) {
        return;
      }
      if ($firstBreak == true) {
        echo "\n";
      }
      echo "\033[".$color."m".$title.": ".$message."\n\033[0m";
    }

    // Line Break + Color Reset
    function lb_cr() {
      echo "\n\033[0m";
    }

    // Fail Count Var
    $fail_count = 0;

    // Sync vars
    $ssh_hostname = $_ENV['LIVE_SSH_HOSTNAME'];
    $ssh_username = $_ENV['LIVE_SSH_USERNAME'];
    $rem_proj_loc = $_ENV['REMOTE_PROJECT_LOCATION'];

    // Exit if some vars missing
    if (empty($ssh_hostname) || empty($ssh_username) || empty($rem_proj_loc)) {

      // Exit Messages
      task_message('some/all dev sync vars are not set in .env file', 'Error', 31);

      // Line Break + Color Reset + Exit
      lb_cr();
      exit();

    }

    // Plugin Vars
    $dev_activated_plugins = $_ENV['DEV_ACTIVATED_PLUGINS'];
    $dev_deactivated_plugins = $_ENV['DEV_DEACTIVATED_PLUGINS'];

    // Move to project root
    chdir(ABSPATH.'../../');

    /**
     * TASK: Database Sync
     */
    $task_name = 'Sync Database';
    task_message($task_name);

    // pv check
    if (`which pv`) {
      $pipe = '| pv |';
    } else {
      task_message('Install the \'pv\' command to monitor import progress', 'Notice', 33, false);
      $pipe = '|';
    }

    $command = 'ssh '.$ssh_username.'@'.$ssh_hostname.' "bash -c \"cd '.$rem_proj_loc.' && '.$rem_proj_loc.'/vendor/bin/wp db export --single-transaction -\"" '.$pipe.' wp db import -';
    debug_message($command);
    system($command);

    /**
     * TASK: Post sync queries
     */
    if ($queries = $_ENV['DEV_POST_SYNC_QUERIES']) {
      $command = 'wp db query "' . preg_replace('/(`|")/i', '\\\\${1}', $queries) . '"';
      debug_message($command);
      system($command);
    }


    /**
     * TASK: Sync Uploads Folder
     */
    $task_name = 'Sync Uploads Folder';

    $excludes  = '';
    if ($exclude_dirs = $_ENV['DEV_SYNC_DIR_EXCLUDES']) {
      $exclude_dirs = explode(',', $exclude_dirs);
      foreach ($exclude_dirs as $dir) {
        $excludes .= ' --exclude=' . $dir;
      }
    }

    if (`which rsync`) {
      task_message($task_name);
      $command = 'rsync -avhP '.$ssh_username.'@'.$ssh_hostname.':'.$rem_proj_loc.'/web/app/uploads/ ./web/app/uploads/' . $excludes;
      debug_message($command);
      system($command);
    } else {
      task_message($task_name.' task not ran, please install \'rsync\'', 'Error', 31);
      $fail_count++;
    }

    /**
     * TASK: Activate / Deactivate Plugins
     */

    // Activate Plugins
    if (!empty($dev_activated_plugins)) {
      task_message('Activate Plugins');
      $cleaned_arr_list = preg_replace('/[ ,]+/', ' ', trim($dev_activated_plugins));
      $command = 'wp plugin activate '.$cleaned_arr_list;
      debug_message($command);
      system($command);
    }

    // Deactivate Plugins
    if (!empty($dev_deactivated_plugins)) {
      task_message('Deactivate Plugins');
      $cleaned_arr_list = preg_replace('/[ ,]+/', ' ', trim($dev_deactivated_plugins));
      $command = 'wp plugin deactivate '.$cleaned_arr_list;
      debug_message($command);
      system($command);
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

  WP_CLI::add_command( 'sync', $sync);
}