<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Updates panel on the settings page: the version running, whether a newer one
 * is out, and a button to check GitHub now instead of waiting for WordPress's
 * twice daily check.
 */
class SBBT_Updates {

	const NOTICE = 'sbbt_update_notice_';

	public static function boot() {
		add_action( 'admin_post_sbbt_check_updates', [ __CLASS__, 'check' ] );
	}

	private static function checker() {
		return isset( $GLOBALS['sbbt_update_checker'] ) ? $GLOBALS['sbbt_update_checker'] : null;
	}

	public static function check() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to do that.', 'sb-bricks-tweaks' ) );
		}

		check_admin_referer( 'sbbt_check_updates' );

		$checker = self::checker();
		$type    = 'error';
		$message = __( 'The update checker is not running on this site.', 'sb-bricks-tweaks' );

		if ( $checker ) {
			delete_transient( 'sbbt_latest_release' );

			try {
				$update = $checker->checkForUpdates();
				$type   = 'success';

				if ( $update && ! empty( $update->version ) && version_compare( $update->version, SBBT_VERSION, '>' ) ) {
					/* translators: %s: version number */
					$message = sprintf( __( 'Version %s is available.', 'sb-bricks-tweaks' ), $update->version );
				} else {
					$message = __( 'You are running the latest version.', 'sb-bricks-tweaks' );
				}
			} catch ( \Throwable $e ) {
				$message = __( 'Could not reach GitHub. Try again shortly.', 'sb-bricks-tweaks' );
			}
		}

		set_transient(
			self::NOTICE . get_current_user_id(),
			[
				'type'    => $type,
				'message' => $message,
			],
			5 * MINUTE_IN_SECONDS
		);

		wp_safe_redirect( admin_url( 'admin.php?page=' . SBBT_Settings::PAGE_SLUG . '-updates' ) );
		exit;
	}

	public static function render() {
		$file    = plugin_basename( SBBT_FILE );
		$state   = get_site_transient( 'update_plugins' );
		$pending = ( $state && ! empty( $state->response[ $file ]->new_version ) ) ? $state->response[ $file ]->new_version : '';
		$checked = ( $state && ! empty( $state->last_checked ) ) ? (int) $state->last_checked : 0;
		$notice  = get_transient( self::NOTICE . get_current_user_id() );
		$repo    = 'https://github.com/' . SBBT_GITHUB_REPO . '/releases';

		if ( $notice ) {
			delete_transient( self::NOTICE . get_current_user_id() );
		}
		?>
		<section class="sbbt-section" id="sbbt-section-updates">
			<div class="sbbt-section__head">
				<h2><?php esc_html_e( 'Updates', 'sb-bricks-tweaks' ); ?></h2>
				<p><?php esc_html_e( 'Delivered from the hub site through GitHub releases. WordPress checks twice a day on its own.', 'sb-bricks-tweaks' ); ?></p>
			</div>

			<?php if ( is_array( $notice ) ) : ?>
				<div class="notice notice-<?php echo $notice['type'] === 'success' ? 'success' : 'error'; ?> inline">
					<p><?php echo esc_html( $notice['message'] ); ?></p>
				</div>
			<?php endif; ?>

			<div class="sbbt-updates">
				<p class="sbbt-updates__status">
					<?php if ( $pending ) : ?>
						<span class="sbbt-updates__badge is-available"><?php echo esc_html( 'v' . $pending . ' ' . __( 'available', 'sb-bricks-tweaks' ) ); ?></span>
					<?php else : ?>
						<span class="sbbt-updates__badge is-current"><?php esc_html_e( 'Up to date', 'sb-bricks-tweaks' ); ?></span>
					<?php endif; ?>

					<span class="sbbt-updates__meta">
						<?php
						/* translators: %s: version number */
						printf( esc_html__( 'Running v%s.', 'sb-bricks-tweaks' ), esc_html( SBBT_VERSION ) );

						if ( $checked ) {
							echo ' ';
							/* translators: %s: time since the last check, e.g. 3 hours */
							printf( esc_html__( 'Checked %s ago.', 'sb-bricks-tweaks' ), esc_html( human_time_diff( $checked ) ) );
						}
						?>
					</span>
				</p>

				<div class="sbbt-updates__actions">
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<input type="hidden" name="action" value="sbbt_check_updates">
						<?php wp_nonce_field( 'sbbt_check_updates' ); ?>
						<button type="submit" class="button"><?php esc_html_e( 'Check for updates', 'sb-bricks-tweaks' ); ?></button>
					</form>

					<?php
					// The bulk path the dashboard uses, which swaps the files under maintenance
					// mode and never deactivates the plugin. The single plugin path deactivates
					// first and reactivates silently, and when that silent step fails the plugin
					// is simply left off with nothing logged. It happened.
					if ( $pending && current_user_can( 'update_plugins' ) ) :
						?>
						<a class="button button-primary" href="<?php echo esc_url( wp_nonce_url( self_admin_url( 'update-core.php?action=do-plugin-upgrade&plugins=' . rawurlencode( $file ) ), 'upgrade-core' ) ); ?>"><?php esc_html_e( 'Update now', 'sb-bricks-tweaks' ); ?></a>
					<?php endif; ?>

					<a class="sbbt-updates__link" href="<?php echo esc_url( $repo ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'All releases', 'sb-bricks-tweaks' ); ?></a>
				</div>
			</div>
		</section>
		<?php
	}
}