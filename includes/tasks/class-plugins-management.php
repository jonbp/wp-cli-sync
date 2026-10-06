<?php
/**
 * Plugin activation task.
 *
 * @package WP_CLI_Sync
 */

namespace WP_CLI_Sync\Tasks;

use WP_CLI_Sync\Task;

/**
 * Activates and deactivates the DEV_ACTIVATED_PLUGINS and
 * DEV_DEACTIVATED_PLUGINS lists.
 */
class Plugins_Management extends Task {

	/**
	 * Applies each list that's been set.
	 */
	public function run() {
		$plugin_actions = array(
			'activate'   => $this->config->activated_plugins,
			'deactivate' => $this->config->deactivated_plugins,
		);

		foreach ( $plugin_actions as $action => $plugin_list ) {
			if ( ! empty( $plugin_list ) ) {
				$this->apply( $action, $plugin_list );
			}
		}
	}

	/**
	 * Activates or deactivates a list of plugins.
	 *
	 * @param string $action      'activate' or 'deactivate'.
	 * @param string $plugin_list Plugin slugs, comma or space separated.
	 */
	private function apply( $action, $plugin_list ) {
		$this->output->task_start( ucfirst( $action ) . ' Plugins' );

		$plugins = preg_replace( '/[ ,]+/', ' ', trim( $plugin_list ) );

		list( $status, $output ) = $this->shell->exec( $this->config->local_wp_cli . ' plugin ' . $action . ' ' . $plugins );

		// Missing plugins only warn, so they don't fail the sync.
		if ( 0 !== $status ) {
			$this->output->task_result( 'Not every plugin could be ' . $action . 'd', 'warning' );

			// Fall back to everything if WP-CLI's wording ever changes.
			$warnings = preg_grep( '/^(Warning|Error):/', $output );
			$this->output->block( $warnings ? $warnings : $output );
			return;
		}

		$this->output->task_result( ucfirst( $action ) . 'd ' . str_replace( ' ', ', ', $plugins ) );
	}
}
