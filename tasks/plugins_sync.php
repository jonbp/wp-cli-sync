<?php

/**
 * TASK: Sync Plugins Folder
 *
 * Bedrock keeps its plugins under composer's control, so they're only pulled
 * down on a vanilla project, where nothing else tracks them.
 */
$task_name = 'Sync Plugins Folder';

if ($is_bedrock) {
  debug_message('Bedrock project detected, '.$task_name.' task skipped');
  return;
}

task_start('Plugins');

if (shell_exec('which rsync')) {
  list($rsync_status, $rsync_files, $rsync_bytes, $rsync_errors) = sync_rsync($ssh_username . '@' . $ssh_hostname . ':' . $rem_proj_loc . '/' . $plugin_dir . '/', './' . $plugin_dir . '/');

  if ($rsync_status !== 0 && $rsync_status !== 24) {
    task_result($task_name.' failed (rsync exit code '.$rsync_status.')', 'error');
    sync_output($rsync_errors);
    $fail_count++;
  } else {
    task_result(sync_rsync_summary($rsync_files, $rsync_bytes));
  }
} else {
  task_result($task_name.' task not ran, please install \'rsync\'', 'error');
  $fail_count++;
}
