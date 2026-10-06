<?php
/**
 * Database sync task.
 *
 * @package WP_CLI_Sync
 */

namespace WP_CLI_Sync\Tasks;

use WP_CLI_Sync\Output;
use WP_CLI_Sync\Task;

/**
 * Imports the live database, then runs any post-sync queries.
 */
class Database_Sync extends Task {

	/**
	 * Runs the import, and the queries if it succeeded.
	 */
	public function run() {
		$this->output->task_start( 'Database' );

		if ( $this->import() && $this->config->post_sync_queries ) {
			$this->run_queries();
		}
	}

	/**
	 * Streams the live export into a local import, showing progress.
	 *
	 * The export is gzipped on the way, when the live server has gzip and PHP
	 * can inflate it. SQL compresses well, so far less has to cross the network.
	 *
	 * @return bool Whether the import succeeded.
	 */
	private function import() {
		$config   = $this->config;
		$compress = $this->sync->remote_gzip && function_exists( 'inflate_init' );
		$export   = $config->remote_wp_cli . ' db export --single-transaction -';

		// pipefail, so a failed export isn't hidden behind gzip's exit code.
		if ( $compress ) {
			$export = 'set -o pipefail; ' . $export . ' | gzip -c';
		}

		$export = $config->ssh() . ' ' . $config->ssh_target() . ' "bash -c \"cd ' . $config->remote_project . ' && ' . $export . '\""';
		$import = $config->local_wp_cli . ' db import - --quiet';

		$start     = microtime( true );
		$last_draw = 0;

		$progress = function ( $bytes ) use ( $start, &$last_draw ) {
			if ( microtime( true ) - $last_draw > 0.1 ) {
				$rate = $bytes / max( microtime( true ) - $start, 0.001 );
				$this->output->status_line( '    Importing ' . Output::bytes( $bytes ) . ' · ' . Output::bytes( $rate ) . '/s' );
				$last_draw = microtime( true );
			}
		};

		$filter         = null;
		$inflate_failed = false;

		if ( $compress ) {
			$inflate = inflate_init( ZLIB_ENCODING_GZIP );
			$filter  = function ( $chunk, $is_last ) use ( $inflate, &$inflate_failed ) {
				// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- Corrupt data is reported below.
				$sql = @inflate_add( $inflate, $chunk, $is_last ? ZLIB_FINISH : ZLIB_SYNC_FLUSH );

				if ( false === $sql ) {
					$inflate_failed = true;
				}

				return $sql;
			};
		}

		list( $export_status, $import_status, $stopped, $transferred, $bytes, $export_errors, $import_errors ) = $this->shell->pipe( $export, $import, $progress, $filter );

		// Either side failing fails the sync. A dead import also kills the
		// export's ssh with SIGPIPE, so blame the import when writing to it failed.
		$import_failed = ( $stopped && ! $inflate_failed ) || ( 0 !== $import_status && 0 === $export_status );

		if ( $import_failed ) {
			$status = $import_status ? $import_status : 1;
		} else {
			$status = $export_status;
		}

		// Bad data from an export that otherwise succeeded. A failed export is
		// reported as such below.
		if ( $inflate_failed && 0 === $export_status ) {
			$this->fail( 'Database sync failed (the compressed export could not be read)', $export_errors );
			return false;
		}

		if ( 0 !== $status ) {
			$this->fail( 'Database sync failed (' . ( $import_failed ? 'import' : 'export' ) . ' exit code ' . $status . ')', $import_failed ? $import_errors : $export_errors );
			return false;
		}

		$this->output->task_result( 'Imported ' . Output::bytes( $bytes ) . ( $compress ? ' (' . Output::bytes( $transferred ) . ' transferred)' : '' ) );
		return true;
	}

	/**
	 * Runs DEV_POST_SYNC_QUERIES.
	 */
	private function run_queries() {
		$queries = preg_replace( '/(`|")/i', '\\\\${1}', $this->config->post_sync_queries );

		list( $status, $output ) = $this->shell->exec( $this->config->local_wp_cli . ' db query "' . $queries . '"' );

		if ( 0 !== $status ) {
			$this->fail( 'Post-sync queries failed', $output );
			return;
		}

		$this->output->task_result( 'Ran post-sync queries' );
		$this->output->block( $output );
	}
}
