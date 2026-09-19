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

if (`which rsync`) {
  task_message($task_name);
  $command = 'rsync -avhP ' . escapeshellarg($ssh_username . '@' . $ssh_hostname . ':' . $rem_proj_loc . '/' . $plugin_dir . '/') . ' ' . escapeshellarg('./' . $plugin_dir . '/');
  debug_message($command);
  system($command, $rsync_status);

  if ($rsync_status !== 0 && $rsync_status !== 24) {
    task_message($task_name.' failed (rsync exit code '.$rsync_status.')', 'Error', 31);
    $fail_count++;
  }
} else {
  task_message($task_name.' task not ran, please install \'rsync\'', 'Error', 31);
  $fail_count++;
}
