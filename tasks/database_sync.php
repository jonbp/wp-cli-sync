<?php

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