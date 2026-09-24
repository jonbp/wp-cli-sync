<?php

// Welcome
sync_header($live_domain ? $live_domain.' → '.$dev_domain : '');
task_start('Connection');
sync_status_line('    Checking settings and connection...');

/**
 * BEGIN VAR / CONNECTION CHECKS
 */

// Exit if some vars missing
if (empty($ssh_hostname) || empty($ssh_username) || empty($rem_proj_loc)) {

  // Exit Messages
  task_result('LIVE_SSH_USERNAME, LIVE_SSH_HOSTNAME and REMOTE_PROJECT_LOCATION must all be set', 'error');

  // Deactivate maintenance mode + Exit
  sync_exit($local_wp_cli);

}

// Check if Remote location formatted correctly
if(($rem_proj_loc[0] != '/') && ($rem_proj_loc[0] != '~')) {

  // Exit Messages
  task_result('Incorrect formatting of the REMOTE_PROJECT_LOCATION variable', 'error');
  task_message('Ensure that the path begins with either / or ~/', 'Hint', 33);

  // Deactivate maintenance mode + Exit
  sync_exit($local_wp_cli);

} elseif($rem_proj_loc[0] == '~') {

  if($rem_proj_loc[1] != '/') {

    // Exit Messages
    task_result('Incorrect formatting of the REMOTE_PROJECT_LOCATION variable', 'error');
    task_message('Ensure that the path begins with either / or ~/', 'Hint', 33);

    // Deactivate maintenance mode + Exit
    sync_exit($local_wp_cli);

  }

}

// Check if SSH connection works
$command = 'ssh -q '.$ssh_username.'@'.$ssh_hostname.' exit; echo $?';
$live_server_status = exec($command);

if ($live_server_status == '255') {

  // Exit Messages
  task_result('Cannot connect to '.$ssh_username.'@'.$ssh_hostname.' over SSH', 'error');
  task_message('Check that your LIVE_SSH_HOSTNAME and LIVE_SSH_USERNAME variables are correct', 'Hint', 33);

  // Deactivate maintenance mode + Exit
  sync_exit($local_wp_cli);

}

// Check if WP-CLI is installed on live server
$command = 'ssh -q '.$ssh_username.'@'.$ssh_hostname.' "bash -c \"test -f '.$remote_wp_cli.' && echo true || echo false\""';
$live_server_check = exec($command);

// Anything but an explicit 'true' (e.g. no output from a dropped connection) is a failure
if ($live_server_check !== 'true') {

  // Exit Messages
  task_result('Connected but cannot find remote WP-CLI at '.$remote_wp_cli, 'error');
  task_message('Check that WP-CLI is installed on the live server and that REMOTE_WP_CLI points at it', 'Hint', 33);

  // Deactivate maintenance mode + Exit
  sync_exit($local_wp_cli);

}

// Checks Success
task_result('Connected to '.$ssh_username.'@'.$ssh_hostname);
