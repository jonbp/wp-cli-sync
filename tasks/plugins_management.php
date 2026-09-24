<?php

/**
 * TASK: Activate / Deactivate Plugins
 */
$plugin_actions = array(
  'activate' => $dev_activated_plugins,
  'deactivate' => $dev_deactivated_plugins
);

foreach ($plugin_actions as $action => $plugin_list) {
  if (empty($plugin_list)) {
    continue;
  }

  task_start(ucfirst($action).' Plugins');
  $cleaned_arr_list = preg_replace('/[ ,]+/', ' ', trim($plugin_list));
  list($status, $output) = sync_exec($local_wp_cli . ' plugin '.$action.' '.$cleaned_arr_list);

  // Missing plugins only warn, so they don't fail the sync
  if ($status !== 0) {
    task_result('Not every plugin could be '.$action.'d', 'warning');
    // Fall back to everything if WP-CLI's wording ever changes
    sync_output(preg_grep('/^(Warning|Error):/', $output) ?: $output);
  } else {
    task_result(ucfirst($action).'d '.str_replace(' ', ', ', $cleaned_arr_list));
  }
}
