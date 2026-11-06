<?php
/**
 * Uploads sync task.
 *
 * @package WP_CLI_Sync
 */

namespace WP_CLI_Sync\Tasks;

/**
 * Pulls down the uploads folder, minus any DEV_SYNC_DIR_EXCLUDES.
 *
 * Skipped when DEV_UPLOADS_PROXY is on, as missing uploads are fetched from
 * the live site instead.
 */
class Uploads_Sync extends Folder_Sync {

	/**
	 * Syncs the uploads folder.
	 */
	public function run() {
		if ( $this->config->uploads_proxy ) {
			$this->output->task_start( 'Uploads' );
			$this->output->task_result( 'Skipped, missing uploads are served from the live site (DEV_UPLOADS_PROXY)' );
			return;
		}

		$this->sync_folder( 'Uploads', 'Sync Uploads Folder' );
	}

	/**
	 * The uploads folder.
	 *
	 * @return string
	 */
	protected function dir() {
		return $this->config->upload_dir;
	}

	/**
	 * An --exclude for each DEV_SYNC_DIR_EXCLUDES folder.
	 *
	 * @return string
	 */
	protected function rsync_args() {
		$excludes = '';
		if ( $this->config->sync_dir_excludes ) {
			foreach ( explode( ',', $this->config->sync_dir_excludes ) as $dir ) {
				$excludes .= ' --exclude=' . escapeshellarg( $dir );
			}
		}
		return $excludes;
	}
}
