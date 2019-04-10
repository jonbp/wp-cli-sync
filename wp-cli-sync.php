<?php
/*
Plugin Name:  WP-CLI Sync
Description:  A WP-CLI command to sync the database and uploads from a live site to a development environment
Version:      1.0.0
Author:       Jon Beaumont-Pike
Author URI:   https://jonbp.co.uk/
License:      MIT License
*/

if ( defined( 'WP_CLI' ) && WP_CLI ) {
  $sync = function() {

    // Task Message
    function task_message($message, $title='Task', $color = 34, $firstBreak = true) {
      if($firstBreak == true) {
        echo "\n";
      }
      echo "\033[".$color."m".$title.": ".$message."\n\033[0m";
    }

    // Fail Count Var
    $fail_count = 0;

    // Sync vars
    $ssh_hostname = env('LIVE_SSH_HOSTNAME');
    $ssh_username = env('LIVE_SSH_USERNAME');
    $rem_proj_loc = env('REMOTE_PROJECT_LCOATION');

    // Exit if some vars missing
    if(empty($ssh_hostname) || empty($ssh_username) || empty($rem_proj_loc)) {
      exit('Error: some/all dev sync vars are not set in .env file');
    }

    // Plugin Vars
    $dev_activated_plugins = env('DEV_ACTIVATED_PLUGINS');
    $dev_deactivated_plugins = env('DEV_DEACTIVATED_PLUGINS');

    // Move to project root
    chdir(ABSPATH.'../../');

    /**
     * TASK: Database Sync
     */
    $task_name = 'Sync Database';
    if (`which wp`) {
      task_message($task_name);

      // pv check
      if (`which pv`) {
        $pipe = '| pv |';
      } else {
        task_message('Install the \'pv\' command to monitor import progress', 'Notice', 33, false);
        $pipe = '|';
      }
      
      $command = 'ssh '.$ssh_username.'@'.$ssh_hostname.' "cd '.$rem_proj_loc.' & '.$rem_proj_loc.'/vendor/bin/wp db export -" '.$pipe.' wp db import -';
      system($command);
    } else {
      task_message('WP-CLI is not installed. Unable to run the '.$task_name.' task', 'Error', 31);
      $fail_count++;
    }

    /**
     * TASK: Sync Uploads Folder
     */
    $task_name = 'Sync Uploads Folder';
    if (`which rsync`) {
      task_message($task_name);
      $command = 'rsync -avhP '.$ssh_username.'@'.$ssh_hostname.':'.$rem_proj_loc.'/web/app/uploads/ ./web/app/uploads/';
      system($command);
    } else {
      task_message($task_name.' task not ran, please install \'rsync\'', 'Error', 31);
      $fail_count++;
    }

    /**
     * TASK: Activate / Deactivate Plugins
     */
    if (`which wp`) {

      // Activate Plugins
      if(!empty($dev_activated_plugins)) {
        task_message('Activate Plugins');
        $cleaned_arr_list = preg_replace('/[ ,]+/', ' ', trim($dev_activated_plugins));
        $command = 'wp plugin activate '.$cleaned_arr_list;
        system($command);
      }

      // Deactivate Plugins
      if(!empty($dev_deactivated_plugins)) {
        task_message('Deactivate Plugins');
        $cleaned_arr_list = preg_replace('/[ ,]+/', ' ', trim($dev_deactivated_plugins));
        $command = 'wp plugin deactivate '.$cleaned_arr_list;
        system($command);
      }

    } else {
      task_message('WP-CLI is not installed. Unable to activate / deactivate plugins', 'Error', 31);
      $fail_count++;
    }

    // Completion Message
    if ($fail_count > 0) { 
      task_message('Finished with '.$fail_count. ' errors', 'Warning', 33);
    } else {
      task_message('All Tasks Finished', 'Success', 32);
    }

    // Final Line Break + Color Reset
    echo "\n\033[0m";

  };

  WP_CLI::add_command( 'sync', $sync);
}