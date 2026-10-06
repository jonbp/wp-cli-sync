<?php
/**
 * Command runner.
 *
 * Syncing is a matter of driving ssh, rsync and WP-CLI, so this is where the
 * plugin's process and pipe handling lives.
 *
 * phpcs:disable WordPress.PHP.DiscouragedPHPFunctions.system_calls_exec
 * phpcs:disable WordPress.PHP.DiscouragedPHPFunctions.system_calls_shell_exec
 * phpcs:disable WordPress.PHP.DiscouragedPHPFunctions.system_calls_proc_open
 * phpcs:disable WordPress.WP.AlternativeFunctions -- Process pipes, which WP_Filesystem doesn't cover.
 *
 * @package WP_CLI_Sync
 */

namespace WP_CLI_Sync;

/**
 * Runs commands, keeping their output back so it's only shown on failure.
 */
class Shell {

	/**
	 * Output for debug messages and progress.
	 *
	 * @var Output
	 */
	private $output;

	/**
	 * Sets up the runner.
	 *
	 * @param Output $output Output for debug messages and progress.
	 */
	public function __construct( Output $output ) {
		$this->output = $output;
	}

	/**
	 * Runs a command.
	 *
	 * @param string $command Shell command.
	 * @return array Exit status and output lines, stdout and stderr combined.
	 */
	public function exec( $command ) {
		$this->output->debug( $command );
		exec( $command . ' 2>&1', $lines, $status );
		return array( $status, $lines );
	}

	/**
	 * Whether a program is available on the PATH.
	 *
	 * @param string $program Program name.
	 * @return bool
	 */
	public function has( $program ) {
		return (bool) shell_exec( 'which ' . escapeshellarg( $program ) );
	}

	/**
	 * Pipes one command's output into another, reporting progress as it goes.
	 *
	 * @param string        $source   Command writing to stdout.
	 * @param string        $target   Command reading from stdin.
	 * @param callable      $progress Called with the bytes written to the target so far.
	 * @param callable|null $filter   Optional. Transforms each chunk on its way through,
	 *                                e.g. to decompress it. Called with the chunk and
	 *                                whether it's the end of the stream, returning the
	 *                                data to write, or false to stop.
	 * @return array {
	 *     @type int      $source_status Source exit status.
	 *     @type int      $target_status Target exit status.
	 *     @type bool     $stopped       Whether the target stopped reading, or the filter failed.
	 *     @type int      $bytes_read    Bytes read from the source.
	 *     @type int      $bytes_written Bytes written to the target.
	 *     @type string[] $source_errors Source stderr lines.
	 *     @type string[] $target_errors Target stdout and stderr lines.
	 * }
	 */
	public function pipe( $source, $target, $progress, $filter = null ) {
		$this->output->debug( $source . ' | ' . $target );

		// Errors are kept back so they don't break up the progress line.
		$source_errors = tmpfile();
		$target_errors = tmpfile();
		$source_spec   = array(
			1 => array( 'pipe', 'w' ),
			2 => $source_errors,
		);
		$target_spec   = array(
			0 => array( 'pipe', 'r' ),
			1 => $target_errors,
			2 => $target_errors,
		);
		$source_proc   = proc_open( $source, $source_spec, $source_pipes );
		$target_proc   = proc_open( $target, $target_spec, $target_pipes );

		$bytes_read    = 0;
		$bytes_written = 0;
		$stopped       = false;

		while ( ! $stopped ) {
			$chunk   = fread( $source_pipes[1], 65536 );
			$is_last = feof( $source_pipes[1] );

			if ( false === $chunk ) {
				$chunk = '';
			}

			$bytes_read += strlen( $chunk );

			if ( $filter ) {
				$chunk = $filter( $chunk, $is_last );
			}

			if ( false === $chunk ) {
				$stopped = true;
				break;
			}

			// The target has died. Writing to it warns, and its exit code is picked up below.
			// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			if ( '' !== $chunk && false === @fwrite( $target_pipes[0], $chunk ) ) {
				$stopped = true;
				break;
			}

			if ( '' !== $chunk ) {
				$bytes_written += strlen( $chunk );
				$progress( $bytes_written );
			}

			if ( $is_last ) {
				break;
			}
		}

		fclose( $source_pipes[1] );
		fclose( $target_pipes[0] );

		$source_status = proc_close( $source_proc );
		$target_status = proc_close( $target_proc );

		$result = array( $source_status, $target_status, $stopped, $bytes_read, $bytes_written, self::read_tmpfile( $source_errors ), self::read_tmpfile( $target_errors ) );

		fclose( $source_errors );
		fclose( $target_errors );

		return $result;
	}

	/**
	 * Pulls a remote folder down with rsync, showing a single live progress line
	 * rather than every file.
	 *
	 * @param string $source rsync source.
	 * @param string $dest   rsync destination.
	 * @param string $args   Extra arguments, already escaped.
	 * @return array Exit status, files updated, their size in bytes and stderr lines.
	 */
	public function rsync( $source, $dest, $args = '' ) {
		$command = 'rsync -ah --partial --out-format=' . escapeshellarg( '%l %n' ) . ' ' . escapeshellarg( $source ) . ' ' . escapeshellarg( $dest ) . $args;
		$this->output->debug( $command );

		$stderr  = tmpfile();
		$process = proc_open(
			$command,
			array(
				1 => array( 'pipe', 'w' ),
				2 => $stderr,
			),
			$pipes
		);

		$files     = 0;
		$bytes     = 0;
		$last_draw = 0;

		while ( true ) {
			$line = fgets( $pipes[1] );

			if ( false === $line ) {
				break;
			}

			list( $size, $name ) = array_pad( explode( ' ', rtrim( $line, "\n" ), 2 ), 2, '' );

			// Directories carry a trailing slash and aren't worth counting.
			if ( '' === $name || '/' === substr( $name, -1 ) ) {
				continue;
			}

			++$files;
			$bytes += (int) $size;

			// Redrawing on every file slows big transfers down.
			if ( microtime( true ) - $last_draw > 0.05 ) {
				$this->output->status_line( '    ' . Output::count( $files, 'file', 'files' ) . ' · ' . Output::bytes( $bytes ) . ' · ' . $name );
				$last_draw = microtime( true );
			}
		}

		fclose( $pipes[1] );
		$status = proc_close( $process );
		$this->output->clear_line();

		$errors = self::read_tmpfile( $stderr );
		fclose( $stderr );

		return array( $status, $files, $bytes, $errors );
	}

	/**
	 * Lines written to a tmpfile() handle, e.g. a process's captured stderr.
	 *
	 * @param resource $handle File handle.
	 * @return string[]
	 */
	private static function read_tmpfile( $handle ) {
		rewind( $handle );
		return explode( "\n", stream_get_contents( $handle ) );
	}
}
