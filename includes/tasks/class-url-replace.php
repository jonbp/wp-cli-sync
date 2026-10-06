<?php
/**
 * URL replacement task.
 *
 * @package WP_CLI_Sync
 */

namespace WP_CLI_Sync\Tasks;

use WP_CLI_Sync\Output;
use WP_CLI_Sync\Task;

/**
 * Replaces the live domain with the dev domain throughout the database.
 */
class URL_Replace extends Task {

	/**
	 * Runs the replacement, if a live domain has been set.
	 */
	public function run() {
		$config = $this->config;

		if ( empty( $config->live_domain ) ) {
			return;
		}

		$this->output->task_start( 'Site URLs' );

		// Every variant of the live domain left behind in the database.
		$live_urls = array(
			'http://' . $config->live_domain,
			'https://' . $config->live_domain,
			'http://www.' . $config->live_domain,
			'https://www.' . $config->live_domain,
		);

		// One WP-CLI process for the lot, as loading WordPress is most of the
		// cost of each step.
		$command = $config->local_wp_cli . ' eval-file ' . escapeshellarg( dirname( __DIR__ ) . '/scripts/replace-urls.php' ) . ' "' . $config->dev_url . '" "' . implode( '" "', $live_urls ) . '"';

		list( $status, $output ) = $this->shell->exec( $command );

		$result = json_decode( (string) end( $output ), true );

		if ( 0 !== $status || ! is_array( $result ) ) {
			$this->fail( 'Could not replace the site URLs', $output );
			return;
		}

		$errors       = $result['errors'];
		$replacements = $result['replacements'];

		if ( $errors ) {
			$this->fail( 'Could not replace every site URL', $errors );
			return;
		}

		$this->output->task_result( $config->live_domain . ' → ' . $config->dev_url . ', ' . Output::count( $replacements, 'replacement', 'replacements' ) );
	}
}
