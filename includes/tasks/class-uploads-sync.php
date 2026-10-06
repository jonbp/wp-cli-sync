<?php
/**
 * Uploads sync task.
 *
 * @package WP_CLI_Sync
 */

namespace WP_CLI_Sync\Tasks;

/**
 * Pulls down the uploads folder, minus any DEV_SYNC_DIR_EXCLUDES.
 */
class Uploads_Sync extends Folder_Sync {

	/**
	 * Syncs the uploads folder.
	 */
	public function run() {
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
