<?php
/**
 * Uploads proxy.
 *
 * @package WP_CLI_Sync
 */

namespace WP_CLI_Sync;

/**
 * Redirects requests for uploads that aren't on this machine to the live site,
 * so the uploads folder doesn't need syncing.
 *
 * Web servers serve files that exist themselves and only hand missing ones to
 * WordPress, so anything in the local uploads folder is still served from
 * disk, including files uploaded locally. Only switched on by
 * DEV_UPLOADS_PROXY, and never on the live site itself, as the plugin is
 * installed there too.
 */
class Uploads_Proxy {

	/**
	 * Settings.
	 *
	 * @var Config
	 */
	private $config;

	/**
	 * Sets up the proxy.
	 *
	 * @param Config $config Settings.
	 */
	public function __construct( Config $config ) {
		$this->config = $config;
	}

	/**
	 * Hooks the proxy in when DEV_UPLOADS_PROXY and LIVE_DOMAIN are set.
	 *
	 * It runs on plugins_loaded, early enough to skip loading the theme, but
	 * late enough that plugins filtering upload_dir are taken into account.
	 */
	public static function register() {
		$config = new Config();

		if ( ! $config->uploads_proxy || ! $config->live_domain ) {
			return;
		}

		add_action( 'plugins_loaded', array( new self( $config ), 'maybe_redirect' ), 1 );
	}

	/**
	 * Redirects to the live copy if this request is for a missing upload.
	 */
	public function maybe_redirect() {
		$target = $this->target();

		if ( ! $target || headers_sent() ) {
			return;
		}

		// A 302, so browsers don't remember it and a synced copy takes over.
		header( 'Cache-Control: no-cache' );
		header( 'Location: ' . $target, true, 302 );
		exit;
	}

	/**
	 * The live URL for this request, if it's for a missing upload.
	 *
	 * @return string|false False when the request should be left alone.
	 */
	private function target() {
		if ( ! isset( $_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD'] ) || ! in_array( $_SERVER['REQUEST_METHOD'], array( 'GET', 'HEAD' ), true ) ) {
			return false;
		}

		if ( $this->is_live_host() ) {
			return false;
		}

		$uploads = wp_upload_dir( null, false );

		if ( ! empty( $uploads['error'] ) ) {
			return false;
		}

		$base = untrailingslashit( (string) wp_parse_url( $uploads['baseurl'], PHP_URL_PATH ) );

		// Not sanitized, as that would strip percent-encoded characters. The
		// path is only used once it's been checked and re-encoded below.
		$path = (string) wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		if ( 0 !== strpos( $path, $base . '/' ) ) {
			return false;
		}

		$file = rawurldecode( substr( $path, strlen( $base ) + 1 ) );

		// Nothing that could step outside the uploads folder.
		if ( '' === $file || false !== strpos( $file, "\0" ) || preg_match( '#(^|[/\\\\])\.\.([/\\\\]|$)#', $file ) ) {
			return false;
		}

		// The web server would normally serve this itself. If it hasn't, leave
		// it to WordPress rather than redirecting away from a local file.
		if ( file_exists( $uploads['basedir'] . '/' . $file ) ) {
			return false;
		}

		return 'https://' . $this->config->live_domain . $this->live_base( $base ) . '/' . implode( '/', array_map( 'rawurlencode', explode( '/', $file ) ) );
	}

	/**
	 * The uploads path on the live site.
	 *
	 * The same as the local one, less any folder the local site sits in, e.g.
	 * /mysite in localhost/mysite/wp-content/uploads.
	 *
	 * @param string $base Local uploads path.
	 * @return string
	 */
	private function live_base( $base ) {
		$home = untrailingslashit( (string) wp_parse_url( home_url(), PHP_URL_PATH ) );

		if ( '' !== $home && 0 === strpos( $base, $home . '/' ) ) {
			return substr( $base, strlen( $home ) );
		}

		return $base;
	}

	/**
	 * Whether this request is to the live domain, so it never redirects to
	 * itself if the setting reaches the live server.
	 *
	 * @return bool
	 */
	private function is_live_host() {
		if ( ! isset( $_SERVER['HTTP_HOST'] ) ) {
			return false;
		}

		$host = strtolower( preg_replace( '/:\d+$/', '', sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) ) );
		$live = strtolower( preg_replace( '/^www\./i', '', $this->config->live_domain ) );

		return preg_replace( '/^www\./', '', $host ) === $live;
	}
}
