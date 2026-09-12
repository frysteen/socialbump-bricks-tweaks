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

	/**
	 * SB Bricks Tweaks gets its own admin menu, just below Bricks.
	 * It lands on the feature switches. Modules that are switched on can add their own pages under it.
	 */
	public function add_menu() {
		add_menu_page(
			esc_html__( 'SocialBUMP Bricks Tweaks', 'sb-bricks-tweaks' ),
			esc_html__( 'SB Bricks Tweaks', 'sb-bricks-tweaks' ),
			'manage_options',
			self::PAGE_SLUG,
			[ $this, 'render' ],
			$this->menu_icon(),
			$this->menu_position()
		);

		// First sub item, so the menu reads "Features" rather than repeating the plugin name.
		add_submenu_page(
			self::PAGE_SLUG,
			esc_html__( 'SocialBUMP Bricks Tweaks', 'sb-bricks-tweaks' ),
			esc_html__( 'Features', 'sb-bricks-tweaks' ),
			'manage_options',
			self::PAGE_SLUG,
			[ $this, 'render' ]
		);

		foreach ( SBBT_Modules::instance()->enabled() as $id => $module ) {
			if ( empty( $module['admin_page']['title'] ) || empty( $module['admin_page']['render'] ) || ! is_callable( $module['admin_page']['render'] ) ) {
				continue;
			}

			add_submenu_page(
				self::PAGE_SLUG,
				esc_html( $module['admin_page']['title'] ),
				esc_html( $module['admin_page']['title'] ),
				'manage_options',
				self::module_page_slug( $id ),
				function () use ( $module ) {
					$this->render_module_page( $module );
				}
			);
		}
	}

	/**
	 * One card setting field. Shown while the module's switch is on.
	 */
	private function render_field( $module_id, $key, $field ) {
		$type    = isset( $field['type'] ) ? $field['type'] : 'text';
		$label   = isset( $field['label'] ) ? $field['label'] : $key;
		$default = isset( $field['default'] ) ? (string) $field['default'] : '';
		$value   = SBBT_Modules::instance()->setting( $module_id, $key );
		$name    = 'sbbt_settings[' . $module_id . '][' . $key . ']';
		$field_id = 'sbbt-' . sanitize_key( $module_id ) . '-' . sanitize_key( $key );

		echo '<div class="sbbt-field sbbt-field--' . esc_attr( $type ) . '">';

		if ( $type === 'checkbox' ) {
			printf(
				'<label for="%1$s"><input type="checkbox" id="%1$s" name="%2$s" value="1" %3$s> %4$s</label>',
				esc_attr( $field_id ),
				esc_attr( $name ),
				checked( ! empty( $value ), true, false ),
				esc_html( $label )
			);
		} else {
			printf( '<label class="sbbt-field__label" for="%s">%s</label>', esc_attr( $field_id ), esc_html( $label ) );

			switch ( $type ) {
				case 'color':
					// Text box takes hex, rgb()/hsl() or var(--name). The swatch fills in a hex.
					$swatch = preg_match( '/^#[0-9a-f]{6}$/i', (string) $value ) ? (string) $value : '#ffffff';

					printf(
						'<span class="sbbt-colour"><input type="color" class="sbbt-colour__swatch" value="%s" aria-label="%s"><input type="text" class="sbbt-colour__value code" id="%s" name="%s" value="%s" placeholder="%s" spellcheck="false" autocomplete="off"></span>',
						esc_attr( $swatch ),
						esc_attr( $label ),
						esc_attr( $field_id ),
						esc_attr( $name ),
						esc_attr( (string) $value ),
						esc_attr( isset( $field['placeholder'] ) ? $field['placeholder'] : '' )
					);
					break;

				case 'select':
					printf( '<select id="%s" name="%s">', esc_attr( $field_id ), esc_attr( $name ) );

					foreach ( (array) ( isset( $field['options'] ) ? $field['options'] : [] ) as $option => $option_label ) {
						printf( '<option value="%s" %s>%s</option>', esc_attr( $option ), selected( (string) $value, (string) $option, false ), esc_html( $option_label ) );
					}

					echo '</select>';
					break;

				case 'number':
					printf(
						'<input type="number" class="small-text" id="%s" name="%s" value="%s"%s%s%s>',
						esc_attr( $field_id ),
						esc_attr( $name ),
						esc_attr( (string) $value ),
						isset( $field['min'] ) ? ' min="' . esc_attr( $field['min'] ) . '"' : '',
						isset( $field['max'] ) ? ' max="' . esc_attr( $field['max'] ) . '"' : '',
						isset( $field['step'] ) ? ' step="' . esc_attr( $field['step'] ) . '"' : ''
					);
					break;

				default:
					printf( '<input type="text" class="regular-text" id="%s" name="%s" value="%s">', esc_attr( $field_id ), esc_attr( $name ), esc_attr( (string) $value ) );
			}
		}

		if ( ! empty( $field['description'] ) ) {
			echo '<p class="sbbt-field__desc">' . esc_html( $field['description'] ) . '</p>';
		}

		echo '</div>';
	}

	/**
	 * Dark SocialBUMP banner at the top of every SB Bricks Tweaks page.
	 * The hr after it tells WordPress to put admin notices below the banner, not inside it.
	 */
	private function render_header( $title, $intro = '' ) {
		?>
		<div class="sbbt-header">
			<div class="sbbt-header__brand">
				<img class="sbbt-header__logo" src="<?php echo esc_url( SBBT_URL . 'assets/img/socialbump-logo-light.svg' ); ?>" alt="SocialBUMP" width="203" height="28">
				<h1 class="sbbt-header__title"><?php echo esc_html( $title ); ?></h1>
			</div>
			<?php if ( $intro !== '' ) : ?>
				<p class="sbbt-header__intro"><?php echo esc_html( $intro ); ?></p>
			<?php endif; ?>
		</div>
		<hr class="wp-header-end">
		<?php
	}

	public static function module_page_slug( $id ) {
		return self::PAGE_SLUG . '-' . sanitize_key( $id );
	}

	/**
	 * Standard frame for a module's own settings page.
	 */
	public function render_module_page( $module ) {
		?>
		<div class="wrap sbbt-wrap">
			<?php $this->render_header( $module['admin_page']['title'], isset( $module['admin_page']['description'] ) ? $module['admin_page']['description'] : '' ); ?>
			<?php call_user_func( $module['admin_page']['render'], $module ); ?>
		</div>
		<?php
	}

	/**
	 * A small toggle switch icon. WordPress recolours SVG data icons to match the admin menu.
	 */
	private function menu_icon() {
		$svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path fill="black" fill-rule="evenodd" d="M6.5 5h7a5 5 0 0 1 0 10h-7a5 5 0 0 1 0-10zm7 2.4a2.6 2.6 0 1 0 0 5.2 2.6 2.6 0 0 0 0-5.2z"/></svg>';

		return 'data:image/svg+xml;base64,' . base64_encode( $svg );
	}

	/**
	 * Just below the Bricks menu when it's there.
	 */
	private function menu_position() {
		global $menu;

		foreach ( (array) $menu as $position => $item ) {
			if ( isset( $item[2] ) && $item[2] === 'bricks' ) {
				return (float) $position + 0.1;
			}
		}

		return null;
	}

	public function styles( $hook ) {
		if ( strpos( (string) $hook, self::PAGE_SLUG ) === false ) {
			return;
		}

		$file = SBBT_PATH . 'assets/css/admin.css';
		$ver  = file_exists( $file ) ? SBBT_VERSION . '.' . filemtime( $file ) : SBBT_VERSION;

		wp_enqueue_style( 'sbbt-admin', SBBT_URL . 'assets/css/admin.css', [], $ver );

		$js     = SBBT_PATH . 'assets/js/admin.js';
		$js_ver = file_exists( $js ) ? SBBT_VERSION . '.' . filemtime( $js ) : SBBT_VERSION;

		wp_enqueue_script( 'sbbt-admin', SBBT_URL . 'assets/js/admin.js', [ 'jquery' ], $js_ver, true );
	}

	public function save() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to do that.', 'sb-bricks-tweaks' ) );
		}

		check_admin_referer( 'sbbt_save' );

		$modules   = SBBT_Modules::instance()->all();
		$saved     = (array) get_option( SBBT_OPTION, [] );
		$submitted = isset( $_POST['sbbt_modules'] ) ? (array) wp_unslash( $_POST['sbbt_modules'] ) : [];
		$states    = [];

		foreach ( $modules as $id => $module ) {
			// A greyed out module can't be changed here, so keep whatever it was set to before.
			if ( SBBT_Modules::instance()->missing( $id ) ) {
				$states[ $id ] = array_key_exists( $id, $saved ) ? (int) (bool) $saved[ $id ] : (int) (bool) $module['default'];
				continue;
			}

			$states[ $id ] = ! empty( $submitted[ $id ] ) ? 1 : 0;
		}

		update_option( SBBT_OPTION, $states );

		// Card settings.
		$posted_settings = isset( $_POST['sbbt_settings'] ) ? (array) wp_unslash( $_POST['sbbt_settings'] ) : [];
		$saved_settings  = (array) get_option( SBBT_Modules::SETTINGS_OPTION, [] );

		foreach ( $modules as $id => $module ) {
			// Greyed out modules don't show their settings, so keep what they had.
			if ( empty( $module['settings'] ) || SBBT_Modules::instance()->missing( $id ) ) {
				continue;
			}

			foreach ( $module['settings'] as $key => $field ) {
				$raw = isset( $posted_settings[ $id ][ $key ] ) ? $posted_settings[ $id ][ $key ] : null;

				$saved_settings[ $id ][ $key ] = SBBT_Modules::instance()->sanitize_setting( $field, $raw );
			}
		}

		update_option( SBBT_Modules::SETTINGS_OPTION, $saved_settings );

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

	/**
	 * Boxes on the settings page, in display order.
	 * A module picks its box with 'section' in its module.php.
	 */
	public function sections() {
		$sections = [
			'elements'   => [
				'title'       => __( 'Bricks Elements', 'sb-bricks-tweaks' ),
				'description' => __( 'Custom elements added to the Bricks element panel.', 'sb-bricks-tweaks' ),
			],
			'conditions' => [
				'title'       => __( 'Conditional Logic', 'sb-bricks-tweaks' ),
				'description' => __( 'Extra options in the Bricks element Conditions panel, listed under the SocialBUMP group.', 'sb-bricks-tweaks' ),
			],
			'extras'     => [
				'title'       => __( 'Extras', 'sb-bricks-tweaks' ),
				'description' => __( 'Other tweaks to how Bricks works.', 'sb-bricks-tweaks' ),
			],
		];

		return (array) apply_filters( 'sbbt/settings_sections', $sections );
	}

	public function render() {
		$modules  = SBBT_Modules::instance()->all();
		$states   = SBBT_Modules::instance()->get_states();
		$sections = $this->sections();
		$fallback = isset( $sections['extras'] ) ? 'extras' : key( $sections );
		$grouped  = [];

		foreach ( $modules as $id => $module ) {
			$section = ( ! empty( $module['section'] ) && isset( $sections[ $module['section'] ] ) ) ? $module['section'] : $fallback;

			$grouped[ $section ][ $id ] = $module;
		}
		?>
		<div class="wrap sbbt-wrap">
			<?php
			$this->render_header(
				__( 'Bricks Tweaks', 'sb-bricks-tweaks' ),
				__( 'Switch each feature on or off. Anything switched off is not loaded at all, so it adds nothing to the site.', 'sb-bricks-tweaks' )
			);
			?>

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

					<?php foreach ( $sections as $key => $section ) : ?>
						<?php
						if ( empty( $grouped[ $key ] ) ) {
							continue;
						}
						?>
						<section class="sbbt-section" id="sbbt-section-<?php echo esc_attr( $key ); ?>">
							<div class="sbbt-section__head">
								<h2><?php echo esc_html( $section['title'] ); ?></h2>
								<?php if ( ! empty( $section['description'] ) ) : ?>
									<p><?php echo esc_html( $section['description'] ); ?></p>
								<?php endif; ?>
							</div>

							<div class="sbbt-grid">
								<?php foreach ( $grouped[ $key ] as $id => $module ) : ?>
									<?php
									$missing = SBBT_Modules::instance()->missing( $id );
									$on      = ! empty( $states[ $id ] ) && ! $missing;
									?>
									<div class="sbbt-card<?php echo $on ? ' is-on' : ''; ?><?php echo $missing ? ' is-unavailable' : ''; ?>">
										<div class="sbbt-card__head">
											<h3><?php echo esc_html( $module['title'] ); ?></h3>
											<label class="sbbt-switch">
												<input type="checkbox" name="sbbt_modules[<?php echo esc_attr( $id ); ?>]" value="1" <?php checked( $on ); ?> <?php disabled( (bool) $missing ); ?>>
												<span class="sbbt-switch__track"><span class="sbbt-switch__dot"></span></span>
												<span class="screen-reader-text"><?php echo esc_html( $module['title'] ); ?></span>
											</label>
										</div>

										<?php if ( $module['description'] ) : ?>
											<p class="sbbt-card__desc"><?php echo esc_html( $module['description'] ); ?></p>
										<?php endif; ?>

										<?php if ( ! $missing && ! empty( $module['settings'] ) ) : ?>
											<div class="sbbt-card__settings">
												<?php
												foreach ( $module['settings'] as $key => $field ) {
													$this->render_field( $id, $key, $field );
												}
												?>
											</div>
										<?php endif; ?>

										<?php if ( $on && ! empty( $module['admin_page']['title'] ) ) : ?>
											<p class="sbbt-card__link"><a href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::module_page_slug( $id ) ) ); ?>"><?php esc_html_e( 'Settings', 'sb-bricks-tweaks' ); ?></a></p>
										<?php endif; ?>

										<?php if ( $missing ) : ?>
											<p class="sbbt-card__needs">
												<?php
												/* translators: %s: plugin name(s) */
												printf( esc_html__( 'Needs %s installed and active.', 'sb-bricks-tweaks' ), esc_html( implode( ' and ', $missing ) ) );
												?>
											</p>
										<?php endif; ?>
									</div>
								<?php endforeach; ?>
							</div>
						</section>
					<?php endforeach; ?>

					<?php submit_button( esc_html__( 'Save changes', 'sb-bricks-tweaks' ) ); ?>
				</form>
			<?php endif; ?>

			<?php do_action( 'sbbt_settings_after' ); ?>
		</div>
		<?php
	}
}