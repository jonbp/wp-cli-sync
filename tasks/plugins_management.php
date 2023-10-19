<?php

/**
 * TASK: Activate / Deactivate Plugins
 */

// Activate Plugins
if (!empty($dev_activated_plugins)) {
  task_message('Activate Plugins');
  $cleaned_arr_list = preg_replace('/[ ,]+/', ' ', trim($dev_activated_plugins));
  $command = ABSPATH . '/../../vendor/bin/wp plugin activate '.$cleaned_arr_list;
  debug_message($command);
  system($command);
}

// Deactivate Plugins
if (!empty($dev_deactivated_plugins)) {
  task_message('Deactivate Plugins');
  $cleaned_arr_list = preg_replace('/[ ,]+/', ' ', trim($dev_deactivated_plugins));
  $command = ABSPATH . '/../../vendor/bin/wp plugin deactivate '.$cleaned_arr_list;
  debug_message($command);
  system($command);
}
