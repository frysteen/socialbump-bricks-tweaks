<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Settings screen under the Bricks admin menu.
 */
class SBBT_Settings {

	private static $instance = null;

	const PAGE_SLUG = 'sb-bricks-tweaks';

	public static function instance() {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function boot() {
		add_action( 'admin_menu', [ $this, 'add_menu' ], 20 );
		add_action( 'admin_post_sbbt_save', [ $this, 'save' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'styles' ] );
	}

	public function add_menu() {
		add_submenu_page(
			'bricks',
			esc_html__( 'SB Tweaks', 'sb-bricks-tweaks' ),
			esc_html__( 'SB Tweaks', 'sb-bricks-tweaks' ),
			'manage_options',
			self::PAGE_SLUG,
			[ $this, 'render' ]
		);
	}

	public function styles( $hook ) {
		if ( strpos( (string) $hook, self::PAGE_SLUG ) === false ) {
			return;
		}

		wp_enqueue_style( 'sbbt-admin', SBBT_URL . 'assets/css/admin.css', [], SBBT_VERSION );
	}

	public function save() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to do that.', 'sb-bricks-tweaks' ) );
		}

		check_admin_referer( 'sbbt_save' );

		$modules  = SBBT_Modules::instance()->all();
		$submitted = isset( $_POST['sbbt_modules'] ) ? (array) wp_unslash( $_POST['sbbt_modules'] ) : [];
		$states    = [];

		foreach ( $modules as $id => $module ) {
			$states[ $id ] = ! empty( $submitted[ $id ] ) ? 1 : 0;
		}

		update_option( SBBT_OPTION, $states );

		wp_safe_redirect(
			add_query_arg(
				[
					'page'    => self::PAGE_SLUG,
					'updated' => 'true',
				],
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	public function render() {
		$modules = SBBT_Modules::instance()->all();
		$states  = SBBT_Modules::instance()->get_states();
		?>
		<div class="wrap sbbt-wrap">
			<h1><?php esc_html_e( 'SocialBUMP Bricks Tweaks', 'sb-bricks-tweaks' ); ?></h1>
			<p class="sbbt-intro">
				<?php esc_html_e( 'Switch each element or tweak on or off. Anything switched off is not loaded at all, so it adds nothing to the page.', 'sb-bricks-tweaks' ); ?>
			</p>

			<?php if ( isset( $_GET['updated'] ) ) : ?>
				<div class="notice notice-success is-dismissible">
					<p><?php esc_html_e( 'Settings saved.', 'sb-bricks-tweaks' ); ?></p>
				</div>
			<?php endif; ?>

			<?php if ( empty( $modules ) ) : ?>
				<p><?php esc_html_e( 'No modules found yet.', 'sb-bricks-tweaks' ); ?></p>
			<?php else : ?>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="sbbt_save">
					<?php wp_nonce_field( 'sbbt_save' ); ?>

					<div class="sbbt-grid">
						<?php foreach ( $modules as $id => $module ) : ?>
							<div class="sbbt-card<?php echo ! empty( $states[ $id ] ) ? ' is-on' : ''; ?>">
								<div class="sbbt-card__head">
									<h2><?php echo esc_html( $module['title'] ); ?></h2>
									<label class="sbbt-switch">
										<input type="checkbox" name="sbbt_modules[<?php echo esc_attr( $id ); ?>]" value="1" <?php checked( ! empty( $states[ $id ] ) ); ?>>
										<span class="sbbt-switch__track"><span class="sbbt-switch__dot"></span></span>
										<span class="screen-reader-text"><?php echo esc_html( $module['title'] ); ?></span>
									</label>
								</div>

								<?php if ( $module['description'] ) : ?>
									<p class="sbbt-card__desc"><?php echo esc_html( $module['description'] ); ?></p>
								<?php endif; ?>

								<p class="sbbt-card__meta">
									<?php
									if ( $module['type'] === 'element' ) {
										echo esc_html__( 'Bricks element', 'sb-bricks-tweaks' );
									} else {
										echo esc_html__( 'Site tweak', 'sb-bricks-tweaks' );
									}
									?>
								</p>
							</div>
						<?php endforeach; ?>
					</div>

					<?php submit_button( esc_html__( 'Save changes', 'sb-bricks-tweaks' ) ); ?>
				</form>
			<?php endif; ?>

			<?php do_action( 'sbbt_settings_after' ); ?>
		</div>
		<?php
	}
}