<?php
/**
 * A sync run.
 *
 * @package WP_CLI_Sync
 */

namespace WP_CLI_Sync;

/**
 * Holds the settings and output shared by a sync's tasks, runs them in order
 * and tallies their failures.
 */
class Sync {

	/**
	 * Settings.
	 *
	 * @var Config
	 */
	public $config;

	/**
	 * Terminal output.
	 *
	 * @var Output
	 */
	public $output;

	/**
	 * Command runner.
	 *
	 * @var Shell
	 */
	public $shell;

	/**
	 * Whether the live server has gzip, so the database can be sent compressed.
	 * Set by the connection check.
	 *
	 * @var bool
	 */
	public $remote_gzip = false;

	/**
	 * Failed task count.
	 *
	 * @var int
	 */
	private $failures = 0;

	/**
	 * Sets up the run.
	 *
	 * @param Config $config Settings.
	 */
	public function __construct( Config $config ) {
		$this->config = $config;
		$this->output = new Output( $config->debug );
		$this->shell  = new Shell( $this->output );
	}

	/**
	 * Runs the tasks for the parts being synced. Exits with code 1 if any failed.
	 *
	 * @param bool $database Whether to sync the database.
	 * @param bool $media    Whether to sync the uploads and plugins folders.
	 */
	public function run( $database, $media ) {
		$start = microtime( true );

		// Commands run from the project root.
		chdir( $this->config->local_project );

		// Media-only syncs leave the database alone, so skip maintenance mode.
		if ( $database ) {
			$this->maintenance_mode( 'activate' );
		}

		$tasks = array( Tasks\Connection_Check::class );

		if ( $database ) {
			$tasks[] = Tasks\Database_Sync::class;
			$tasks[] = Tasks\URL_Replace::class;
		}

		if ( $media ) {
			$tasks[] = Tasks\Uploads_Sync::class;
			$tasks[] = Tasks\Plugins_Sync::class;
		}

		if ( $database ) {
			$tasks[] = Tasks\Plugins_Management::class;
		}

		foreach ( $tasks as $task ) {
			( new $task( $this ) )->run();
		}

		if ( $database ) {
			$this->maintenance_mode( 'deactivate' );
		}

		$this->close_ssh();

		if ( $this->failures > 0 ) {
			$this->output->write( "\n" . $this->output->color( '! Finished with ' . Output::count( $this->failures, 'error', 'errors' ) . ' in ' . Output::elapsed( $start ), '1;33' ) . "\n\n" );
			exit( 1 );
		}

		$this->output->write( "\n" . $this->output->color( '✔ Sync complete in ' . Output::elapsed( $start ), '1;32' ) . "\n\n" );
	}

	/**
	 * Counts a failed task, so the sync finishes with an error.
	 */
	public function fail() {
		++$this->failures;
	}

	/**
	 * Stops the sync without leaving the site in maintenance mode.
	 */
	public function abort() {
		$this->maintenance_mode( 'deactivate' );
		$this->close_ssh();
		$this->output->write( "\n" );
		exit( 1 );
	}

	/**
	 * Closes the shared ssh connection, rather than leaving it to time out.
	 */
	private function close_ssh() {
		if ( $this->config->ssh_control_path() ) {
			$this->shell->exec( $this->config->ssh() . ' -O exit ' . $this->config->ssh_target() );
		}
	}

	/**
	 * Turns maintenance mode on or off.
	 *
	 * @param string $action 'activate' or 'deactivate'.
	 */
	private function maintenance_mode( $action ) {
		$this->shell->exec( $this->config->local_wp_cli . ' maintenance-mode ' . $action );
	}
}
