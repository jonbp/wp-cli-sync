<?php

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