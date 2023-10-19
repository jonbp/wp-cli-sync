<?php

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