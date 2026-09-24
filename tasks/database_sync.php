<?php

/**
 * TASK: Database Sync
 *
 * The export is streamed through PHP on its way to the import, so progress can
 * be shown.
 */
task_start('Database');

$export_command = 'ssh '.$ssh_username.'@'.$ssh_hostname.' "bash -c \"cd '.$rem_proj_loc.' && '.$remote_wp_cli.' db export --single-transaction -\""';
$import_command = $local_wp_cli.' db import - --quiet';
debug_message($export_command.' | '.$import_command);

// Errors are kept back so they don't break up the progress line
$export_errors = tmpfile();
$import_errors = tmpfile();
$export = proc_open($export_command, array(1 => array('pipe', 'w'), 2 => $export_errors), $export_pipes);
$import = proc_open($import_command, array(0 => array('pipe', 'r'), 1 => $import_errors, 2 => $import_errors), $import_pipes);

$db_bytes = 0;
$db_start = microtime(true);
$last_draw = 0;
$import_died = false;

while (!feof($export_pipes[1])) {
  $chunk = fread($export_pipes[1], 65536);

  if ($chunk === false || $chunk === '') {
    continue;
  }

  // The import has died, its exit code is picked up below
  if (@fwrite($import_pipes[0], $chunk) === false) {
    $import_died = true;
    break;
  }

  $db_bytes += strlen($chunk);

  if (microtime(true) - $last_draw > 0.1) {
    $rate = $db_bytes / max(microtime(true) - $db_start, 0.001);
    sync_status_line('    Importing '.sync_bytes($db_bytes).' · '.sync_bytes($rate).'/s');
    $last_draw = microtime(true);
  }
}

fclose($export_pipes[1]);
fclose($import_pipes[0]);

// Either side failing fails the sync. A dead import also kills the export's
// ssh with SIGPIPE, so blame the import when writing to it failed.
$export_status = proc_close($export);
$import_status = proc_close($import);
$import_failed = $import_died || ($import_status !== 0 && $export_status === 0);
$db_status = $import_failed ? ($import_status ?: 1) : $export_status;

if ($db_status !== 0) {
  task_result('Database sync failed ('.($import_failed ? 'import' : 'export').' exit code '.$db_status.')', 'error');
  sync_output(sync_read_tmpfile($import_failed ? $import_errors : $export_errors));
  $fail_count++;
} else {
  task_result('Imported '.sync_bytes($db_bytes));
}

fclose($export_errors);
fclose($import_errors);

/**
 * TASK: Post sync queries (skipped if the import failed)
 */
if ($db_status === 0 && ($queries = $_ENV['DEV_POST_SYNC_QUERIES'])) {
  $command = $local_wp_cli . ' db query "' . preg_replace('/(`|")/i', '\\\\${1}', $queries) . '"';
  list($query_status, $query_output) = sync_exec($command);

  if ($query_status !== 0) {
    task_result('Post-sync queries failed', 'error');
    sync_output($query_output);
    $fail_count++;
  } else {
    task_result('Ran post-sync queries');
    sync_output($query_output);
  }
}
