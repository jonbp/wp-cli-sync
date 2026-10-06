<?php
/**
 * Connection check task.
 *
 * @package WP_CLI_Sync
 */

namespace WP_CLI_Sync\Tasks;

use WP_CLI_Sync\Config;
use WP_CLI_Sync\Task;

/**
 * Checks the settings, the SSH connection and the remote WP-CLI binary before
 * anything is synced. Any failure here stops the sync.
 */
class Connection_Check extends Task {

	/**
	 * Runs the checks.
	 */
	public function run() {
		$config = $this->config;

		$this->output->header( $config->live_domain ? $config->live_domain . ' → ' . $config->dev_domain : '' );
		$this->output->task_start( 'Connection' );
		$this->output->status_line( '    Checking settings and connection...' );

		if ( empty( $config->ssh_hostname ) || empty( $config->ssh_username ) || empty( $config->remote_project ) ) {
			$this->abort( 'LIVE_SSH_USERNAME, LIVE_SSH_HOSTNAME and REMOTE_PROJECT_LOCATION must all be set' );
		}

		$remote_project = $config->remote_project;

		if ( ! Config::is_rooted( $remote_project ) || ( '~' === $remote_project[0] && '/' !== ( $remote_project[1] ?? '' ) ) ) {
			$this->abort( 'Incorrect formatting of the REMOTE_PROJECT_LOCATION variable', 'Ensure that the path begins with either / or ~/' );
		}

		// One connection checks the server, WP-CLI and gzip. It's kept open for the
		// rest of the sync.
		$check = 'test -f ' . $config->remote_wp_cli . ' && echo wp-cli; command -v gzip >/dev/null && echo gzip; true';

		list( $status, $lines ) = $this->shell->exec( $config->ssh() . ' -q ' . $config->ssh_target() . ' "bash -c \"' . $check . '\""' );

		// ssh exits with 255 when it can't connect.
		if ( 255 === $status ) {
			$this->abort( 'Cannot connect to ' . $config->ssh_target() . ' over SSH', 'Check that your LIVE_SSH_HOSTNAME and LIVE_SSH_USERNAME variables are correct' );
		}

		// No explicit answer (e.g. from a dropped connection) is a failure too.
		if ( ! in_array( 'wp-cli', $lines, true ) ) {
			$this->abort( 'Connected but cannot find remote WP-CLI at ' . $config->remote_wp_cli, 'Check that WP-CLI is installed on the live server and that REMOTE_WP_CLI points at it' );
		}

		$this->sync->remote_gzip = in_array( 'gzip', $lines, true );

		$this->output->task_result( 'Connected to ' . $config->ssh_target() );
	}

	/**
	 * Reports a problem, with a hint on fixing it, and stops the sync.
	 *
	 * @param string $message Problem.
	 * @param string $hint    How to fix it.
	 */
	private function abort( $message, $hint = '' ) {
		$this->output->task_result( $message, 'error' );
		if ( $hint ) {
			$this->output->message( $hint, 'Hint', 33 );
		}
		$this->sync->abort();
	}
}
