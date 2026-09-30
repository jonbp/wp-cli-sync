<?php

// Only colour and redraw lines when writing to a terminal
function sync_is_tty() {
  static $is_tty = null;
  if ($is_tty === null) {
    $is_tty = function_exists('stream_isatty') && stream_isatty(STDOUT);
  }
  return $is_tty;
}

// Wrap text in an ANSI colour
function sync_color($text, $color) {
  return sync_is_tty() ? "\033[".$color."m".$text."\033[0m" : $text;
}

// Clear the current line, ready to redraw it
function sync_clear_line() {
  if (sync_is_tty()) {
    echo "\r\033[2K";
  }
}

// Redraw a single status line, trimmed to the terminal width
function sync_status_line($text) {
  if (!sync_is_tty()) {
    return;
  }
  $width = class_exists('\cli\Shell') ? (int) \cli\Shell::columns() : 80;
  // Count characters, not bytes, so multibyte names aren't split
  if (function_exists('mb_strimwidth')) {
    $text = mb_strimwidth($text, 0, $width - 1, '…', 'UTF-8');
  } elseif (strlen($text) > $width - 1) {
    $text = substr($text, 0, $width - 4).'...';
  }
  sync_clear_line();
  echo sync_color($text, 90);
}

// Seconds since a microtime(true) start, as a short label
function sync_elapsed($start) {
  $seconds = microtime(true) - $start;
  return $seconds < 60 ? number_format($seconds, 1).'s' : floor($seconds / 60).'m '.round(fmod($seconds, 60)).'s';
}

// Human readable byte count
function sync_bytes($bytes) {
  return size_format($bytes, $bytes >= 1048576 ? 1 : 0) ?: '0 B';
}

// Version, from the plugin header
function sync_version() {
  $data = get_file_data(dirname(__DIR__).'/wp-cli-sync.php', array('version' => 'Version'));
  return $data['version'] ?: 'dev';
}

// Banner shown at the start of a sync, with the source and version
function sync_header($source) {
  $parts = array();
  if ($source) {
    $parts[] = $source;
  }
  $parts[] = sync_color('v'.sync_version(), 90);

  // The project's own colour (#2CCBFE) where the terminal supports it, cyan elsewhere
  $brand = in_array(getenv('COLORTERM'), array('truecolor', '24bit'), true) ? '1;38;2;44;203;254' : '1;36';

  echo "\n".sync_color('○ WP-CLI Sync', $brand).'  '.implode(sync_color(' · ', 90), $parts)."\n";
}

// Task heading. Starts the task's timer.
function task_start($name) {
  $GLOBALS['sync_task_start'] = microtime(true);
  echo "\n".sync_color('› '.$name, '1;34')."\n";
}

// Task outcome, with the time since task_start()
function task_result($message, $type = 'success') {
  $symbols = array(
    'success' => array('✔', 32),
    'warning' => array('!', 33),
    'error' => array('✖', 31)
  );
  list($symbol, $color) = $symbols[$type];

  $time = isset($GLOBALS['sync_task_start']) ? ' '.sync_color('('.sync_elapsed($GLOBALS['sync_task_start']).')', 90) : '';

  sync_clear_line();
  echo '  '.sync_color($symbol.' '.$message, $color).$time."\n";
}

// Standalone message, e.g. errors and hints outside a task
function task_message($message, $title = 'Task', $color = 34) {
  echo '  '.sync_color($title.': ', $color).$message."\n";
}

// Indented block of command output, e.g. errors from a failed command
function sync_output($output) {
  foreach (array_filter(array_map('rtrim', (array) $output), 'strlen') as $line) {
    echo '    '.sync_color($line, 90)."\n";
  }
}

// Debug Message
function debug_message($message) {
  if (empty($_ENV['DEV_TASK_DEBUG'])) {
    return;
  }
  echo '  '.sync_color('$ '.$message, 90)."\n";
}

// Lines written to a tmpfile() handle, e.g. a process's captured stderr
function sync_read_tmpfile($handle) {
  rewind($handle);
  return explode("\n", stream_get_contents($handle));
}

// Run a command, keeping its output back unless it fails
function sync_exec($command) {
  debug_message($command);
  exec($command.' 2>&1', $output, $status);
  return array($status, $output);
}

/**
 * rsync a remote folder down, showing a single live progress line rather than
 * every file. Returns the exit status, files updated, their size and stderr lines.
 */
function sync_rsync($source, $dest, $args = '') {
  $command = 'rsync -ah --partial --out-format='.escapeshellarg('%l %n').' '.escapeshellarg($source).' '.escapeshellarg($dest).$args;
  debug_message($command);

  $stderr = tmpfile();
  $process = proc_open($command, array(1 => array('pipe', 'w'), 2 => $stderr), $pipes);

  $files = 0;
  $bytes = 0;
  $last_draw = 0;

  while (($line = fgets($pipes[1])) !== false) {
    list($size, $name) = array_pad(explode(' ', rtrim($line, "\n"), 2), 2, '');

    // Directories carry a trailing slash and aren't worth counting
    if ($name === '' || substr($name, -1) === '/') {
      continue;
    }

    $files++;
    $bytes += (int) $size;

    // Redrawing on every file slows big transfers down
    if (microtime(true) - $last_draw > 0.05) {
      sync_status_line('    '.number_format($files).($files === 1 ? ' file' : ' files').' · '.sync_bytes($bytes).' · '.$name);
      $last_draw = microtime(true);
    }
  }

  fclose($pipes[1]);
  $status = proc_close($process);
  sync_clear_line();

  $errors = sync_read_tmpfile($stderr);
  fclose($stderr);

  return array($status, $files, $bytes, $errors);
}

// Summary for a finished rsync
function sync_rsync_summary($files, $bytes) {
  if ($files === 0) {
    return 'Already up to date';
  }
  return number_format($files).' '.($files === 1 ? 'file' : 'files').' updated ('.sync_bytes($bytes).')';
}

// Bail out without leaving the site in maintenance mode
function sync_exit($local_wp_cli) {
  exec($local_wp_cli . ' maintenance-mode deactivate 2>&1');
  echo "\n";
  exit(1);
}
