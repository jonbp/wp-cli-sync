<?php

/**
 * TASK: Replace the live domain with the dev domain
 */

// Nothing to do unless a live domain has been set
if (empty($live_domain)) {
  return;
}

task_message('Replace Site URLs');

// Point the site at the dev domain. On bedrock this is cosmetic, as WP_HOME
// and WP_SITEURL take precedence over the stored options.
foreach (array('siteurl', 'home') as $option) {
  $command = $local_wp_cli.' option update '.$option.' "'.$dev_url.'"';
  debug_message($command);
  system($command);
}

// Replace every variant of the live domain left behind in the database
$live_urls = array(
  'http://'.$live_domain,
  'https://'.$live_domain,
  'http://www.'.$live_domain,
  'https://www.'.$live_domain
);

foreach ($live_urls as $live_url) {
  $command = $local_wp_cli.' search-replace "'.$live_url.'" "'.$dev_url.'" --quiet';
  debug_message($command);
  system($command);
}

task_message('Replaced '.$live_domain.' with '.$dev_url, 'Site URLs', 33, false);
