<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Carry the plugin's settings from one site to another.
 *
 * Exports the switches and every module's own options as a JSON file, and takes
 * that file back in on another site. Only this plugin's own settings travel:
 * the GitHub token and anything belonging to WordPress or another plugin is
 * left where it is.
 */
class SBBT_Transfer {

	const NOTICE = 'sbbt_transfer_notice_';

	/** The options that make up a site's setup. */
	private static function options() {
		return [
			'modules'  => SBBT_OPTION,
			'settings' => SBBT_Modules::SETTINGS_OPTION,
		];
	}

	public static function boot() {
		add_action( 'admin_post_sbbt_export_settings', [ __CLASS__, 'export' ] );
		add_action( 'admin_post_sbbt_import_settings', [ __CLASS__, 'import' ] );
	}

	private static function back( $type, $message ) {
		set_transient( self::NOTICE . get_current_user_id(), [ 'type' => $type, 'message' => $message ], 60 );

		wp_safe_redirect( admin_url( 'admin.php?page=' . SBBT_Settings::PAGE_SLUG . '-updates' ) );
		exit;
	}

	private static function guard( $action ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to do that.', 'sb-bricks-tweaks' ) );
		}

		check_admin_referer( $action );
	}

	public static function export() {
		self::guard( 'sbbt_export_settings' );

		$payload = [
			'plugin'  => 'socialbump-bricks-tweaks',
			'version' => SBBT_VERSION,
			'site'    => home_url(),
			'date'    => gmdate( 'c' ),
			'options' => [],
		];

		foreach ( self::options() as $key => $option ) {
			$payload['options'][ $key ] = get_option( $option, [] );
		}

		$host = wp_parse_url( home_url(), PHP_URL_HOST );
		$name = 'bricks-tweaks-settings-' . $host . '-' . gmdate( 'Y-m-d' ) . '.json';
		$json = wp_json_encode( $payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );

		while ( ob_get_level() > 0 ) {
			ob_end_clean();
		}

		$quote = chr( 34 );

		nocache_headers();
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=' . $quote . sanitize_file_name( $name ) . $quote );
		header( 'Content-Length: ' . strlen( $json ) );

		echo $json; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit;
	}

	public static function import() {
		self::guard( 'sbbt_import_settings' );

		if ( empty( $_FILES['sbbt_settings_file']['tmp_name'] ) || ! is_uploaded_file( $_FILES['sbbt_settings_file']['tmp_name'] ) ) {
			self::back( 'error', __( 'Choose a settings file first.', 'sb-bricks-tweaks' ) );
		}

		$raw  = file_get_contents( $_FILES['sbbt_settings_file']['tmp_name'] ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		$data = json_decode( (string) $raw, true );

		if ( ! is_array( $data ) || empty( $data['options'] ) || ! is_array( $data['options'] ) ) {
			self::back( 'error', __( 'That file is not a Bricks Tweaks settings export.', 'sb-bricks-tweaks' ) );
		}

		if ( ! empty( $data['plugin'] ) && $data['plugin'] !== 'socialbump-bricks-tweaks' ) {
			self::back( 'error', __( 'That file belongs to a different plugin.', 'sb-bricks-tweaks' ) );
		}

		$done = 0;

		foreach ( self::options() as $key => $option ) {
			if ( ! isset( $data['options'][ $key ] ) || ! is_array( $data['options'][ $key ] ) ) {
				continue;
			}

			update_option( $option, $data['options'][ $key ] );
			$done++;
		}

		if ( ! $done ) {
			self::back( 'error', __( 'There was nothing in that file to bring in.', 'sb-bricks-tweaks' ) );
		}

		self::back( 'success', __( 'Settings brought in. Anything needing a plugin this site does not have stays switched off.', 'sb-bricks-tweaks' ) );
	}
	/** The export and import panel, shown on the Updates page. */
	public static function render() {
		$notice = get_transient( self::NOTICE . get_current_user_id() );

		if ( $notice ) {
			delete_transient( self::NOTICE . get_current_user_id() );
		}

		$post = esc_url( admin_url( 'admin-post.php' ) );

		echo '<section class="sbbt-section">';
		echo '<div class="sbbt-section__head"><h2>' . esc_html__( 'Settings', 'sb-bricks-tweaks' ) . '</h2>';
		echo '<p>' . esc_html__( 'Take this site setup to another site. Only Bricks Tweaks settings are included.', 'sb-bricks-tweaks' ) . '</p></div>';

		if ( is_array( $notice ) ) {
			echo '<div class="notice notice-' . ( $notice['type'] === 'success' ? 'success' : 'error' ) . ' inline"><p>' . esc_html( $notice['message'] ) . '</p></div>';
		}

		echo '<div class="sbbt-grid">';

		echo '<div class="sbbt-card"><div class="sbbt-card__head"><h3>' . esc_html__( 'Export', 'sb-bricks-tweaks' ) . '</h3></div>';
		echo '<p class="sbbt-card__desc">' . esc_html__( 'Download the switches and every module setting as a JSON file.', 'sb-bricks-tweaks' ) . '</p>';
		echo '<form method="post" action="' . $post . '">';
		echo '<input type="hidden" name="action" value="sbbt_export_settings">';
		wp_nonce_field( 'sbbt_export_settings' );
		echo '<p><button type="submit" class="button">' . esc_html__( 'Download settings', 'sb-bricks-tweaks' ) . '</button></p>';
		echo '</form></div>';

		echo '<div class="sbbt-card"><div class="sbbt-card__head"><h3>' . esc_html__( 'Import', 'sb-bricks-tweaks' ) . '</h3></div>';
		echo '<p class="sbbt-card__desc">' . esc_html__( 'Replaces the settings on this site with the ones in the file. There is no undo.', 'sb-bricks-tweaks' ) . '</p>';
		echo '<form method="post" enctype="multipart/form-data" action="' . $post . '">';
		echo '<input type="hidden" name="action" value="sbbt_import_settings">';
		wp_nonce_field( 'sbbt_import_settings' );
		echo '<p><input type="file" name="sbbt_settings_file" accept="application/json,.json" required></p>';
		echo '<p><button type="submit" class="button" onclick="return confirm(&#39;Replace the Bricks Tweaks settings on this site?&#39;);">' . esc_html__( 'Import settings', 'sb-bricks-tweaks' ) . '</button></p>';
		echo '</form></div>';

		echo '</div></section>';
	}
}
