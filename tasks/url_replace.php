<?php

/**
 * TASK: Replace the live domain with the dev domain
 */

// Nothing to do unless a live domain has been set
if (empty($live_domain)) {
  return;
}

task_start('Site URLs');

$url_errors = array();

// Point the site at the dev domain. On bedrock this is cosmetic, as WP_HOME
// and WP_SITEURL take precedence over the stored options.
foreach (array('siteurl', 'home') as $option) {
  list($status, $output) = sync_exec($local_wp_cli.' option update '.$option.' "'.$dev_url.'" --quiet');
  if ($status !== 0) {
    $url_errors = array_merge($url_errors, $output);
  }
}

// Replace every variant of the live domain left behind in the database
$live_urls = array(
  'http://'.$live_domain,
  'https://'.$live_domain,
  'http://www.'.$live_domain,
  'https://www.'.$live_domain
);

$replacements = 0;

foreach ($live_urls as $live_url) {
  list($status, $output) = sync_exec($local_wp_cli.' search-replace "'.$live_url.'" "'.$dev_url.'" --format=count');
  if ($status !== 0) {
    $url_errors = array_merge($url_errors, $output);
  } else {
    $replacements += (int) end($output);
  }
}

if ($url_errors) {
  task_result('Could not replace every site URL', 'error');
  sync_output($url_errors);
  $fail_count++;
} else {
  task_result($live_domain.' → '.$dev_url.', '.number_format($replacements).' '.($replacements === 1 ? 'replacement' : 'replacements'));
}
