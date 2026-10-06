<?php
/**
 * Base class for folder sync tasks.
 *
 * @package WP_CLI_Sync
 */

namespace WP_CLI_Sync\Tasks;

use WP_CLI_Sync\Output;
use WP_CLI_Sync\Task;

/**
 * Pulls a folder down from the live project with rsync.
 */
abstract class Folder_Sync extends Task {

	/**
	 * The folder to sync.
	 *
	 * @return string Folder relative to the project root, e.g. wp-content/uploads.
	 */
	abstract protected function dir();

	/**
	 * Extra rsync arguments.
	 *
	 * @return string Arguments, already escaped.
	 */
	protected function rsync_args() {
		return '';
	}

	/**
	 * Syncs the folder.
	 *
	 * @param string $heading   Heading shown above the result.
	 * @param string $task_name Name used in error messages.
	 */
	protected function sync_folder( $heading, $task_name ) {
		$this->output->task_start( $heading );

		if ( ! $this->shell->has( 'rsync' ) ) {
			$this->fail( $task_name . ' task not ran, please install \'rsync\'' );
			return;
		}

		$dir = $this->dir();

		list( $status, $files, $bytes, $errors ) = $this->shell->rsync( $this->config->remote_path( $dir ), './' . $dir . '/', ' -e ' . escapeshellarg( $this->config->ssh() ) . $this->rsync_args() );

		// 24 = files vanished mid-transfer, routine on a live uploads folder.
		if ( 0 !== $status && 24 !== $status ) {
			$this->fail( $task_name . ' failed (rsync exit code ' . $status . ')', $errors );
			return;
		}

		$this->output->task_result( 0 === $files ? 'Already up to date' : Output::count( $files, 'file', 'files' ) . ' updated (' . Output::bytes( $bytes ) . ')' );
	}
}
