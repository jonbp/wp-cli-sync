<?php
/**
 * Sync settings.
 *
 * @package WP_CLI_Sync
 */

namespace WP_CLI_Sync;

/**
 * Settings for a sync, read from the environment or wp-config.php constants,
 * with the project layout and WP-CLI binaries detected where they're not set.
 */
class Config {

	/**
	 * Every setting, with its default when neither the environment nor a
	 * constant sets it. Empty paths are detected in the constructor.
	 */
	const SETTINGS = array(
		'LIVE_SSH_USERNAME'       => '',
		'LIVE_SSH_HOSTNAME'       => '',
		'LIVE_DOMAIN'             => '',
		'REMOTE_PROJECT_LOCATION' => '',
		'DEV_DOMAIN'              => '',
		'DEV_ACTIVATED_PLUGINS'   => '',
		'DEV_DEACTIVATED_PLUGINS' => '',
		'DEV_POST_SYNC_QUERIES'   => '',
		'DEV_SYNC_DIR_EXCLUDES'   => '',
		'DEV_TASK_DEBUG'          => '',
		'UPLOAD_DIR'              => '',
		'PLUGIN_DIR'              => '',
		'LOCAL_PROJECT_LOCATION'  => '',
		'LOCAL_WP_CLI'            => '',
		'REMOTE_WP_CLI'           => '',
	);

	/**
	 * SSH user on the live server.
	 *
	 * @var string
	 */
	public $ssh_username;

	/**
	 * Live server hostname.
	 *
	 * @var string
	 */
	public $ssh_hostname;

	/**
	 * Project root on the live server.
	 *
	 * @var string
	 */
	public $remote_project;

	/**
	 * Local project root.
	 *
	 * @var string
	 */
	public $local_project;

	/**
	 * Whether the local project is bedrock based.
	 *
	 * @var bool
	 */
	public $is_bedrock;

	/**
	 * Live domain, without a scheme.
	 *
	 * @var string
	 */
	public $live_domain;

	/**
	 * Dev domain, as given or derived from the live domain.
	 *
	 * @var string
	 */
	public $dev_domain;

	/**
	 * Dev site URL, with a scheme.
	 *
	 * @var string
	 */
	public $dev_url;

	/**
	 * Local WP-CLI binary.
	 *
	 * @var string
	 */
	public $local_wp_cli;

	/**
	 * Remote WP-CLI binary.
	 *
	 * @var string
	 */
	public $remote_wp_cli;

	/**
	 * Uploads folder, relative to the project root.
	 *
	 * @var string
	 */
	public $upload_dir;

	/**
	 * Plugins folder, relative to the project root.
	 *
	 * @var string
	 */
	public $plugin_dir;

	/**
	 * Plugins to activate after a sync, comma separated.
	 *
	 * @var string
	 */
	public $activated_plugins;

	/**
	 * Plugins to deactivate after a sync, comma separated.
	 *
	 * @var string
	 */
	public $deactivated_plugins;

	/**
	 * SQL to run after the import.
	 *
	 * @var string
	 */
	public $post_sync_queries;

	/**
	 * Uploads subfolders to skip, comma separated.
	 *
	 * @var string
	 */
	public $sync_dir_excludes;

	/**
	 * Whether to show each command before it runs.
	 *
	 * @var bool
	 */
	public $debug;

	/**
	 * Builds the settings.
	 *
	 * @param array|null $values Raw settings, as returned by read(). Read from
	 *                           the environment when not given.
	 */
	public function __construct( ?array $values = null ) {
		$env = $values ?? self::read();

		$this->ssh_username        = $env['LIVE_SSH_USERNAME'];
		$this->ssh_hostname        = $env['LIVE_SSH_HOSTNAME'];
		$this->remote_project      = $env['REMOTE_PROJECT_LOCATION'];
		$this->activated_plugins   = $env['DEV_ACTIVATED_PLUGINS'];
		$this->deactivated_plugins = $env['DEV_DEACTIVATED_PLUGINS'];
		$this->post_sync_queries   = $env['DEV_POST_SYNC_QUERIES'];
		$this->sync_dir_excludes   = $env['DEV_SYNC_DIR_EXCLUDES'];
		$this->debug               = ! empty( $env['DEV_TASK_DEBUG'] );

		// Any scheme and trailing slash is stripped so the URLs can be rebuilt.
		$this->live_domain = rtrim( preg_replace( '#^https?://#', '', $env['LIVE_DOMAIN'] ), '/' );

		// The dev domain falls back to a dev. subdomain, and to http.
		$this->dev_domain = rtrim( self::either( $env['DEV_DOMAIN'], $this->live_domain ? 'dev.' . $this->live_domain : '' ), '/' );
		$this->dev_url    = ( $this->dev_domain && ! preg_match( '#^https?://#', $this->dev_domain ) ) ? 'http://' . $this->dev_domain : $this->dev_domain;

		// Bedrock keeps WordPress in web/wp, so its root sits two levels above
		// ABSPATH. A classic install has its root at ABSPATH.
		$bedrock_root     = realpath( ABSPATH . '../../' );
		$this->is_bedrock = ( $bedrock_root && file_exists( $bedrock_root . '/web/wp-config.php' ) );

		$this->local_project = self::either( $env['LOCAL_PROJECT_LOCATION'], $this->is_bedrock ? $bedrock_root : rtrim( ABSPATH, '/' ) );

		// Relative to the project root, or an absolute path.
		$this->local_wp_cli = self::either( $env['LOCAL_WP_CLI'], file_exists( $this->local_project . '/vendor/bin/wp' ) ? 'vendor/bin/wp' : 'wp' );

		// Relative paths are resolved against the remote project location,
		// absolute and home-relative paths are used as given.
		$this->remote_wp_cli = self::either( $env['REMOTE_WP_CLI'], 'vendor/bin/wp' );

		if ( ! self::is_rooted( $this->remote_wp_cli ) ) {
			$this->remote_wp_cli = $this->remote_project . '/' . $this->remote_wp_cli;
		}

		$this->upload_dir = self::either( $env['UPLOAD_DIR'], $this->is_bedrock ? 'web/app/uploads' : 'wp-content/uploads' );

		// Only used on a vanilla project, as bedrock keeps its plugins under
		// composer's control.
		$this->plugin_dir = self::either( $env['PLUGIN_DIR'], 'wp-content/plugins' );
	}

	/**
	 * Reads each setting from the environment, where bedrock's .env lands it,
	 * or from a constant defined in wp-config.php on a vanilla project.
	 *
	 * @return array Values keyed by setting name.
	 */
	public static function read() {
		$values = array();

		foreach ( self::SETTINGS as $name => $default ) {
			$value = getenv( $name );

			if ( empty( $value ) && defined( $name ) ) {
				$value = constant( $name );
			}

			$values[ $name ] = self::either( $value, $default );
		}

		return $values;
	}

	/**
	 * Whether a path starts at / or ~.
	 *
	 * @param string $path Path to check.
	 * @return bool
	 */
	public static function is_rooted( $path ) {
		return isset( $path[0] ) && ( '/' === $path[0] || '~' === $path[0] );
	}

	/**
	 * The live server as user@host, for ssh and rsync.
	 *
	 * @return string
	 */
	public function ssh_target() {
		return $this->ssh_username . '@' . $this->ssh_hostname;
	}

	/**
	 * The ssh command, without a destination, for running remote commands and
	 * as rsync's remote shell.
	 *
	 * Every connection in a sync shares one, rather than each paying for its
	 * own handshake. The first opens it and ControlPersist keeps it open for
	 * the next, until close_ssh() or a minute of inactivity.
	 *
	 * @return string
	 */
	public function ssh() {
		$socket = $this->ssh_control_path();

		if ( ! $socket ) {
			return 'ssh';
		}

		return 'ssh -o ControlMaster=auto -o ControlPersist=60 -o ControlPath=' . escapeshellarg( $socket );
	}

	/**
	 * Path to the shared connection's socket, if it can have one.
	 *
	 * The socket lives in ~/.ssh, as ssh fails outright when its directory is
	 * missing. %C is a 40 character hash of the connection, and ssh adds up to
	 * 17 more characters while creating the socket, which must fit in the 104
	 * characters macOS allows a socket path.
	 *
	 * @return string|false False when connections can't be shared.
	 */
	public function ssh_control_path() {
		$home = getenv( 'HOME' );

		if ( ! $home || ! is_dir( $home . '/.ssh' ) ) {
			return false;
		}

		$path = $home . '/.ssh/wp-cli-sync-%C';

		return strlen( $path ) - 2 + 40 + 17 < 104 ? $path : false;
	}

	/**
	 * A folder in the live project, as an rsync source.
	 *
	 * @param string $dir Folder relative to the project root.
	 * @return string
	 */
	public function remote_path( $dir ) {
		return $this->ssh_target() . ':' . $this->remote_project . '/' . $dir . '/';
	}

	/**
	 * A value, or the fallback when it's empty.
	 *
	 * @param mixed $value    Value to use if set.
	 * @param mixed $fallback Value to use otherwise.
	 * @return mixed
	 */
	private static function either( $value, $fallback ) {
		return $value ? $value : $fallback;
	}
}
