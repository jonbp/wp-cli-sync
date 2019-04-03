<?php
/*
Plugin Name:  WP-CLI Sync
Description:  A WP-CLI command to sync the database and uploads from a live site to a development environment
Version:      1.0.0
Author:       Jon Beaumont-Pike
Author URI:   https://jonbp.co.uk/
License:      MIT License
*/

if ( defined( 'WP_CLI' ) && WP_CLI ) {
  $sync = function() {

    // Sync vars
    $ssh_hostname = env('LIVE_SSH_HOSTNAME');
    $ssh_username = env('LIVE_SSH_USERNAME');
    $rem_proj_loc = env('REMOTE_PROJECT_LCOATION');

    // Exit if some vars missing
    if(empty($ssh_hostname) || empty($ssh_username) || empty($rem_proj_loc)) {
      exit('Error: some/all dev sync vars are not set in .env file');
    }

    // Plugin Vars
    $dev_activated_plugins = env('DEV_ACTIVATED_PLUGINS');
    $dev_deactivated_plugins = env('DEV_DEACTIVATED_PLUGINS');

    // Move to project root
    chdir(ABSPATH.'../../');

    // Get Database
    $command = 'ssh '.$ssh_username.'@'.$ssh_hostname.' "cd '.$rem_proj_loc.' & '.$rem_proj_loc.'/vendor/bin/wp db export -" | pv | ./vendor/bin/wp db import -';
    system($command);

    // Get Uploads
    $command = 'rsync -avhP '.$ssh_username.'@'.$ssh_hostname.':'.$rem_proj_loc.'/web/app/uploads/ ./web/app/uploads/';
    system($command);

    // Activate Plugins
    if(!empty($dev_activated_plugins)) {
      $cleaned_arr_list = preg_replace('/[ ,]+/', ' ', trim($dev_activated_plugins));
      $command = 'wp plugin activate '.$cleaned_arr_list;
      system($command);
    }

    // Deactivate Plugins
    if(!empty($dev_deactivated_plugins)) {
      $cleaned_arr_list = preg_replace('/[ ,]+/', ' ', trim($dev_deactivated_plugins));
      $command = 'wp plugin deactivate '.$cleaned_arr_list;
      system($command);
    }

  };

  WP_CLI::add_command( 'sync', $sync);
}
