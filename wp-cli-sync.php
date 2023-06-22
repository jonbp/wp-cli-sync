<?php
/*
Plugin Name:  WP-CLI Sync
Description:  A WP-CLI command for syncing a live site to a development environment
Version:      1.3.1
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
	'DEV_TASK_DEBUG',
	'UPLOAD_DIR'
);

foreach ($env_variables as $env_variable) {
	$_ENV[$env_variable] = $_ENV[$env_variable] ?? getenv($env_variable) ?? getDefault($env_variable);
}

function getDefault($env_variable): bool|array|string
{
	if ($env_variable === 'UPLOAD_DIR') {
		return getenv($env_variable) ?: 'web/app/uploads';
	} else {
		return '';
	}
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
	$upload_dir = $_ENV['UPLOAD_DIR'];

    // Welcome
    task_message('Running .env file and connection checks...', 'WP-CLI Sync', 97);

    /**
     * BEGIN VAR / CONNECTION CHECKS
     */

    // Exit if some vars missing
    if (empty($ssh_hostname) || empty($ssh_username) || empty($rem_proj_loc)) {

      // Exit Messages
      task_message('some/all dev sync vars are not set in .env file', 'Error', 31, false);

      // Line Break + Color Reset + Exit
      lb_cr();
      exit();

    }

    // Check if Remote location formatted correctly
    if(($rem_proj_loc[0] != '/') && ($rem_proj_loc[0] != '~')) {

      // Exit Messages
      task_message('Incorrect formatting of the REMOTE_PROJECT_LOCATION variable', 'Error', 31, false);
      task_message('Ensure that the path begins with either / or ~/', 'Hint', 33);

      // Line Break + Color Reset + Exit
      lb_cr();
      exit();

    } elseif($rem_proj_loc[0] == '~') {

      if($rem_proj_loc[1] != '/') {

        // Exit Messages
        task_message('Incorrect formatting of the REMOTE_PROJECT_LOCATION variable', 'Error', 31, false);
        task_message('Ensure that the path begins with either / or ~/', 'Hint', 33);

        // Line Break + Color Reset + Exit
        lb_cr();
        exit();

      }

    }

    // Check if SSH connection works
    $command = 'ssh -q '.$ssh_username.'@'.$ssh_hostname.' exit; echo $?';
    $live_server_status = exec($command);

    if ($live_server_status == '255') {

      // Exit Messages
      task_message('Cannot connect to live server over SSH', 'Error', 31, false);
      task_message('Check that your LIVE_SSH_HOSTNAME and LIVE_SSH_USERNAME variables are correct', 'Hint', 33);

      // Line Break + Color Reset + Exit
      lb_cr();
      exit();

    }

    // Check if WP-CLI is installed on live server
    $command = 'ssh -q '.$ssh_username.'@'.$ssh_hostname.' "bash -c \"test -f '.$rem_proj_loc.'/vendor/bin/wp && echo true || echo false\""';
    $live_server_check = exec($command);

    if ($live_server_check == 'false') {

      // Exit Messages
      task_message('Connected but cannot find remote WP-CLI', 'Error', 31, false);
      task_message('Either WP-CLI Sync is not installed on the live server or the REMOTE_PROJECT_LOCATION variable is incorrect', 'Hint', 33);

      // Line Break + Color Reset + Exit
      lb_cr();
      exit();

    }

    // Checks Success
    task_message('Running sync...', 'Connected', 32, false);

    // Plugin Vars
    $dev_activated_plugins = $_ENV['DEV_ACTIVATED_PLUGINS'];
    $dev_deactivated_plugins = $_ENV['DEV_DEACTIVATED_PLUGINS'];

    // Move to project root
    chdir(ABSPATH.'../../');

    // Activate Maintenance Mode
    $command = ABSPATH . '/../../vendor/bin/wp maintenance-mode activate';
    exec($command);

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

    $command = 'ssh '.$ssh_username.'@'.$ssh_hostname.' "bash -c \"cd '.$rem_proj_loc.' && '.$rem_proj_loc.'/vendor/bin/wp db export --single-transaction -\"" '.$pipe. ' ' . ABSPATH . '/../../vendor/bin/wp db import -';
    debug_message($command);
    system($command);

    /**
     * TASK: Post sync queries
     */
    if ($queries = $_ENV['DEV_POST_SYNC_QUERIES']) {
      $command = ABSPATH . '/../../vendor/bin/wp db query "' . preg_replace('/(`|")/i', '\\\\${1}', $queries) . '"';
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
      $command = 'rsync -avhP ' . $ssh_username . '@' . $ssh_hostname . ':' . $rem_proj_loc . '/' . $upload_dir . '/ ./' . $upload_dir . '/' . $excludes;
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
      $command = ABSPATH . '/../../vendor/bin/wp plugin activate '.$cleaned_arr_list;
      debug_message($command);
      system($command);
    }

    // Deactivate Plugins
    if (!empty($dev_deactivated_plugins)) {
      task_message('Deactivate Plugins');
      $cleaned_arr_list = preg_replace('/[ ,]+/', ' ', trim($dev_deactivated_plugins));
      $command = ABSPATH . '/../../vendor/bin/wp plugin deactivate '.$cleaned_arr_list;
      debug_message($command);
      system($command);
    }

    // Deactivate Maintenance Mode
    $command = ABSPATH . '/../../vendor/bin/wp maintenance-mode deactivate';
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