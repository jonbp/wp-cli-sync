<?php

/**
 * TASK: Sync Uploads Folder
 */
$task_name = 'Sync Uploads Folder';

$excludes  = '';
if ($exclude_dirs = $_ENV['DEV_SYNC_DIR_EXCLUDES']) {
  $exclude_dirs = explode(',', $exclude_dirs);
  foreach ($exclude_dirs as $dir) {
    $excludes .= ' --exclude=' . escapeshellarg($dir);
  }
}

task_start('Uploads');

if (shell_exec('which rsync')) {
  list($rsync_status, $rsync_files, $rsync_bytes, $rsync_errors) = sync_rsync($ssh_username . '@' . $ssh_hostname . ':' . $rem_proj_loc . '/' . $upload_dir . '/', './' . $upload_dir . '/', $excludes);

  // 24 = files vanished mid-transfer, routine on a live uploads folder
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
