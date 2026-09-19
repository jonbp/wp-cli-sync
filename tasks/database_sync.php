<?php

/**
 * TASK: Database Sync
 */
$task_name = 'Sync Database';
task_message($task_name);

// pv check
if (shell_exec('which pv')) {
  $pipe = '| pv |';
} else {
  task_message('Install the \'pv\' command to monitor import progress', 'Notice', 33, false);
  $pipe = '|';
}

$command = 'ssh '.$ssh_username.'@'.$ssh_hostname.' "bash -c \"cd '.$rem_proj_loc.' && '.$remote_wp_cli.' db export --single-transaction -\"" '.$pipe.' '.$local_wp_cli.' db import -';
debug_message($command);
// pipefail catches a failed export, not just a failed import
system('bash -c '.escapeshellarg('set -o pipefail; '.$command), $db_status);

if ($db_status !== 0) {
  task_message('Database sync failed (exit code '.$db_status.')', 'Error', 31);
  $fail_count++;
}

/**
 * TASK: Post sync queries (skipped if the import failed)
 */
if ($db_status === 0 && ($queries = $_ENV['DEV_POST_SYNC_QUERIES'])) {
  $command = $local_wp_cli . ' db query "' . preg_replace('/(`|")/i', '\\\\${1}', $queries) . '"';
  debug_message($command);
  system($command);
}
