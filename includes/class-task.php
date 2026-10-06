<?php
/**
 * Base class for sync tasks.
 *
 * @package WP_CLI_Sync
 */

namespace WP_CLI_Sync;

/**
 * One step of a sync, such as pulling down the database or a folder.
 */
abstract class Task {

	/**
	 * The run this task belongs to.
	 *
	 * @var Sync
	 */
	protected $sync;

	/**
	 * Settings.
	 *
	 * @var Config
	 */
	protected $config;

	/**
	 * Terminal output.
	 *
	 * @var Output
	 */
	protected $output;

	/**
	 * Command runner.
	 *
	 * @var Shell
	 */
	protected $shell;

	/**
	 * Sets up the task.
	 *
	 * @param Sync $sync The run this task belongs to.
	 */
	public function __construct( Sync $sync ) {
		$this->sync   = $sync;
		$this->config = $sync->config;
		$this->output = $sync->output;
		$this->shell  = $sync->shell;
	}

	/**
	 * Runs the task.
	 */
	abstract public function run();

	/**
	 * Reports a failure along with any command output, and counts it.
	 *
	 * @param string   $message Failure message.
	 * @param string[] $output  Command output to show.
	 */
	protected function fail( $message, $output = array() ) {
		$this->output->task_result( $message, 'error' );
		$this->output->block( $output );
		$this->sync->fail();
	}
}
