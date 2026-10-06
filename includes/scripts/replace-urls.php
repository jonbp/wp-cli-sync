<?php
/**
 * Points the freshly imported site at the dev URL.
 *
 * Run with `wp eval-file`, so every step shares one WP-CLI process rather
 * than each paying to load WordPress. It runs in its own process, not the
 * sync's, as the sync's WordPress has the old database's options cached.
 *
 * Usage: wp eval-file replace-urls.php <dev-url> <live-url>...
 *
 * Prints a JSON object with the replacement count and any error lines.
 *
 * @package WP_CLI_Sync
 */

( function ( $urls ) {
	$dev_url      = array_shift( $urls );
	$replacements = 0;
	$errors       = array();

	$run = function ( $command ) use ( &$errors ) {
		$result = WP_CLI::runcommand(
			$command,
			array(
				'return'     => 'all',
				'launch'     => false,
				'exit_error' => false,
			)
		);

		if ( 0 !== $result->return_code ) {
			$errors = array_merge( $errors, explode( "\n", $result->stderr ) );
			return false;
		}

		return $result->stdout;
	};

	// On bedrock this is cosmetic, as WP_HOME and WP_SITEURL take precedence
	// over the stored options.
	foreach ( array( 'siteurl', 'home' ) as $option ) {
		$run( 'option update ' . $option . ' "' . $dev_url . '" --quiet' );
	}

	foreach ( $urls as $live_url ) {
		$count = $run( 'search-replace "' . $live_url . '" "' . $dev_url . '" --format=count' );

		if ( false !== $count ) {
			$replacements += (int) $count;
		}
	}

	WP_CLI::line(
		wp_json_encode(
			array(
				'replacements' => $replacements,
				'errors'       => $errors,
			)
		)
	);
} )( $args );
