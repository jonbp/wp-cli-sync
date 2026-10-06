<?php
/**
 * The wp sync command.
 *
 * @package WP_CLI_Sync
 */

namespace WP_CLI_Sync;

use WP_CLI;

/**
 * Syncs the live site down to this development environment.
 *
 * Syncs the database and the uploads folder, plus the plugins folder on a
 * vanilla project. Pass a flag to sync just one part.
 */
class Command {

	const SYNOPSIS = array(
		array(
			'type'        => 'flag',
			'name'        => 'database',
			'optional'    => true,
			'description' => 'Only sync the database',
		),
		array(
			'type'        => 'flag',
			'name'        => 'media',
			'optional'    => true,
			'description' => 'Only sync the uploads folder',
		),
	);

	/**
	 * Registers the command with WP-CLI.
	 */
	public static function register() {
		WP_CLI::add_command( 'sync', self::class, array( 'synopsis' => self::SYNOPSIS ) );
	}

	/**
	 * Runs the sync.
	 *
	 * @param array $args       Positional arguments, unused.
	 * @param array $assoc_args Flags.
	 */
	public function __invoke( $args, $assoc_args ) {
		// --database / --media limit the sync, neither syncs everything.
		$database_flag  = self::flag( $assoc_args, 'database' );
		$media_flag     = self::flag( $assoc_args, 'media' );
		$only_requested = ( true === $database_flag ) || ( true === $media_flag );
		$sync_database  = $database_flag ?? ! $only_requested;
		$sync_media     = $media_flag ?? ! $only_requested;

		if ( ! $sync_database && ! $sync_media ) {
			WP_CLI::error( 'Nothing to sync: both --no-database and --no-media given.' );
		}

		( new Sync( new Config() ) )->run( $sync_database, $sync_media );
	}

	/**
	 * A flag's value, so --database=true/1 and --database=false/0 behave too.
	 *
	 * @param array  $assoc_args Flags.
	 * @param string $name       Flag name.
	 * @return bool|null Null when the flag wasn't passed.
	 */
	private static function flag( $assoc_args, $name ) {
		return isset( $assoc_args[ $name ] ) ? filter_var( $assoc_args[ $name ], FILTER_VALIDATE_BOOLEAN ) : null;
	}
}
