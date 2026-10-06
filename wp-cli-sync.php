<?php
/**
 * Plugin Name:  WP-CLI Sync
 * Description:  A WP-CLI command for syncing a live site to a development environment
 * Version:      1.6.0
 * Author:       Jon Beaumont-Pike
 * Author URI:   https://jonbp.co.uk/
 * License:      MIT License
 *
 * @package WP_CLI_Sync
 */

/*
 * Load classes from includes/. Vanilla installs don't go through composer, so
 * the plugin can't rely on its autoloader. WP_CLI_Sync\Tasks\URL_Replace, for
 * example, lives in includes/tasks/class-url-replace.php.
 */
spl_autoload_register(
	function ( $class_name ) {
		$prefix = 'WP_CLI_Sync\\';

		if ( 0 !== strpos( $class_name, $prefix ) ) {
			return;
		}

		$parts = explode( '\\', strtolower( str_replace( '_', '-', substr( $class_name, strlen( $prefix ) ) ) ) );
		$file  = array_pop( $parts );
		$path  = __DIR__ . '/includes/' . implode( '/', array_merge( $parts, array( 'class-' . $file . '.php' ) ) );

		if ( file_exists( $path ) ) {
			require $path;
		}
	}
);

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI_Sync\Command::register();
}
