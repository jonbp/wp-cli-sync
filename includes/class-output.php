<?php
/**
 * Terminal output.
 *
 * @package WP_CLI_Sync
 */

namespace WP_CLI_Sync;

/**
 * The banner, task headings and results, and the live progress line. Colours
 * and redraws are skipped when not writing to a terminal.
 */
class Output {

	/**
	 * Whether STDOUT is a terminal.
	 *
	 * @var bool
	 */
	private $is_tty;

	/**
	 * Whether to show debug messages.
	 *
	 * @var bool
	 */
	private $debug;

	/**
	 * When the current task started, from microtime(true).
	 *
	 * @var float|null
	 */
	private $task_start;

	/**
	 * Sets up the output.
	 *
	 * @param bool $debug Whether to show debug messages.
	 */
	public function __construct( $debug = false ) {
		$this->debug  = $debug;
		$this->is_tty = function_exists( 'stream_isatty' ) && stream_isatty( STDOUT );
	}

	/**
	 * Wraps text in an ANSI colour.
	 *
	 * @param string     $text  Text to colour.
	 * @param string|int $color ANSI SGR code, such as 32 or '1;34'.
	 * @return string
	 */
	public function color( $text, $color ) {
		return $this->is_tty ? "\033[" . $color . 'm' . $text . "\033[0m" : $text;
	}

	/**
	 * Clears the current line, ready to redraw it.
	 */
	public function clear_line() {
		if ( $this->is_tty ) {
			$this->write( "\r\033[2K" );
		}
	}

	/**
	 * Redraws a single status line, trimmed to the terminal width.
	 *
	 * @param string $text Status text.
	 */
	public function status_line( $text ) {
		if ( ! $this->is_tty ) {
			return;
		}

		$width = class_exists( '\cli\Shell' ) ? (int) \cli\Shell::columns() : 80;

		// Count characters, not bytes, so multibyte names aren't split.
		if ( function_exists( 'mb_strimwidth' ) ) {
			$text = mb_strimwidth( $text, 0, $width - 1, '…', 'UTF-8' );
		} elseif ( strlen( $text ) > $width - 1 ) {
			$text = substr( $text, 0, $width - 4 ) . '...';
		}

		$this->clear_line();
		$this->write( $this->color( $text, 90 ) );
	}

	/**
	 * Shows the banner at the start of a sync, with the source and version.
	 *
	 * @param string $source What's being synced, e.g. 'example.com → dev.example.com'.
	 */
	public function header( $source ) {
		$parts = array();
		if ( $source ) {
			$parts[] = $source;
		}
		$parts[] = $this->color( 'v' . self::version(), 90 );

		// The project's own colour (#2CCBFE) where the terminal supports it, cyan elsewhere.
		$brand = in_array( getenv( 'COLORTERM' ), array( 'truecolor', '24bit' ), true ) ? '1;38;2;44;203;254' : '1;36';

		$this->write( "\n" . $this->color( '○ WP-CLI Sync', $brand ) . '  ' . implode( $this->color( ' · ', 90 ), $parts ) . "\n" );
	}

	/**
	 * Shows a task heading and starts the task's timer.
	 *
	 * @param string $name Task name.
	 */
	public function task_start( $name ) {
		$this->task_start = microtime( true );
		$this->write( "\n" . $this->color( '› ' . $name, '1;34' ) . "\n" );
	}

	/**
	 * Shows a task's outcome, with the time since task_start().
	 *
	 * @param string $message Outcome.
	 * @param string $type    'success', 'warning' or 'error'.
	 */
	public function task_result( $message, $type = 'success' ) {
		$symbols = array(
			'success' => array( '✔', 32 ),
			'warning' => array( '!', 33 ),
			'error'   => array( '✖', 31 ),
		);

		list( $symbol, $color ) = $symbols[ $type ];

		$time = $this->task_start ? ' ' . $this->color( '(' . self::elapsed( $this->task_start ) . ')', 90 ) : '';

		$this->clear_line();
		$this->write( '  ' . $this->color( $symbol . ' ' . $message, $color ) . $time . "\n" );
	}

	/**
	 * Shows a standalone message, e.g. a hint after an error.
	 *
	 * @param string     $message Message.
	 * @param string     $title   Label shown before the message.
	 * @param string|int $color   ANSI colour for the label.
	 */
	public function message( $message, $title = 'Task', $color = 34 ) {
		$this->write( '  ' . $this->color( $title . ': ', $color ) . $message . "\n" );
	}

	/**
	 * Shows an indented block of command output, e.g. errors from a failed command.
	 *
	 * @param string[]|string $output Output lines. Blank lines are skipped.
	 */
	public function block( $output ) {
		foreach ( array_filter( array_map( 'rtrim', (array) $output ), 'strlen' ) as $line ) {
			$this->write( '    ' . $this->color( $line, 90 ) . "\n" );
		}
	}

	/**
	 * Shows a command about to run, when DEV_TASK_DEBUG is set.
	 *
	 * @param string $message Command.
	 */
	public function debug( $message ) {
		if ( $this->debug ) {
			$this->write( '  ' . $this->color( '$ ' . $message, 90 ) . "\n" );
		}
	}

	/**
	 * Seconds since a start time, as a short label such as '4.2s' or '1m 3s'.
	 *
	 * @param float $start Start, from microtime(true).
	 * @return string
	 */
	public static function elapsed( $start ) {
		$seconds = microtime( true ) - $start;
		return $seconds < 60 ? number_format( $seconds, 1 ) . 's' : floor( $seconds / 60 ) . 'm ' . round( fmod( $seconds, 60 ) ) . 's';
	}

	/**
	 * Human readable byte count.
	 *
	 * @param int|float $bytes Byte count.
	 * @return string
	 */
	public static function bytes( $bytes ) {
		$size = size_format( $bytes, $bytes >= MB_IN_BYTES ? 1 : 0 );
		return $size ? $size : '0 B';
	}

	/**
	 * Count with a singular or plural noun, e.g. '1 file' or '1,024 files'.
	 *
	 * @param int    $count    Count.
	 * @param string $singular Noun for one.
	 * @param string $plural   Noun for any other count.
	 * @return string
	 */
	public static function count( $count, $singular, $plural ) {
		return number_format( $count ) . ' ' . ( 1 === $count ? $singular : $plural );
	}

	/**
	 * The plugin version, from its header.
	 *
	 * @return string
	 */
	public static function version() {
		$data = get_file_data( dirname( __DIR__ ) . '/wp-cli-sync.php', array( 'version' => 'Version' ) );
		return $data['version'] ? $data['version'] : 'dev';
	}

	/**
	 * Writes to the terminal.
	 *
	 * @param string $text Text to write.
	 */
	public function write( $text ) {
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Terminal output, not HTML.
		echo $text;
	}
}
