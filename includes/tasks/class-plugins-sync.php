<?php
/**
 * Plugins sync task.
 *
 * @package WP_CLI_Sync
 */

namespace WP_CLI_Sync\Tasks;

/**
 * Pulls down the plugins folder.
 *
 * Bedrock keeps its plugins under composer's control, so they're only pulled
 * down on a vanilla project, where nothing else tracks them.
 */
class Plugins_Sync extends Folder_Sync {

	/**
	 * Syncs the plugins folder, unless this is a bedrock project.
	 */
	public function run() {
		if ( $this->config->is_bedrock ) {
			$this->output->debug( 'Bedrock project detected, Sync Plugins Folder task skipped' );
			return;
		}

		$this->sync_folder( 'Plugins', 'Sync Plugins Folder' );
	}

	/**
	 * The plugins folder.
	 *
	 * @return string
	 */
	protected function dir() {
		return $this->config->plugin_dir;
	}
}
