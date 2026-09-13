<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Finds every module folder, tracks which are switched on, and boots them.
 *
 * To add a module: create includes/modules/<slug>/module.php returning an array.
 * Nothing else in the plugin needs editing.
 *
 * A module can have its own settings page under the SB Bricks Tweaks menu:
 *
 *   'admin_page' => [
 *       'title'  => 'Carousel Settings',             // Menu label and page heading.
 *       'render' => function ( $module ) { ... },   // Prints the page content.
 *   ],
 *
 * The page only appears while the module is switched on and has what it needs.
 *
 * Small options can sit on the module's card instead of a page:
 *
 *   'settings' => [
 *       'colour' => [ 'type' => 'color', 'label' => 'Icon colour', 'default' => '' ],
 *   ],
 *
 * Types: color, checkbox, number (min, max, step), select (options), text.
 * Read a value with SBBT_Modules::instance()->setting( 'module-id', 'colour' ).
 */
class SBBT_Modules {

	private static $instance = null;

	/** Where card settings are saved, keyed by module id. */
	const SETTINGS_OPTION = 'sbbt_module_settings';

	/** All discovered modules, keyed by id. */
	private $modules = [];

	/** Modules that are switched on and have booted. */
	private $active = [];

	/** Cached result of each dependency check. */
	private $dependency_state = [];

	public static function instance() {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function boot() {
		$this->discover();

		add_action( 'init', [ $this, 'register_elements' ], 11 );
		add_action( 'wp_enqueue_scripts', [ $this, 'register_assets' ], 5 );
		add_action( 'admin_enqueue_scripts', [ $this, 'register_assets' ], 5 );

		$this->run_module_hooks();
	}

	/**
	 * Scan the modules folder.
	 */
	private function discover() {
		$dirs = glob( SBBT_PATH . 'includes/modules/*', GLOB_ONLYDIR );

		if ( ! $dirs ) {
			return;
		}

		foreach ( $dirs as $dir ) {
			$file = $dir . '/module.php';

			if ( ! is_readable( $file ) ) {
				continue;
			}

			$module = include $file;

			if ( ! is_array( $module ) || empty( $module['id'] ) ) {
				continue;
			}

			$module = wp_parse_args(
				$module,
				[
					'title'       => $module['id'],
					'description' => '',
					'type'        => 'element',
					'default'     => true,
					'section'     => '',
					'requires'    => [],
					'admin_page'  => null,
					'settings'    => [],
					'path'        => trailingslashit( $dir ),
					'url'         => trailingslashit( SBBT_URL . 'includes/modules/' . basename( $dir ) ),
				]
			);

			if ( $module['section'] === '' ) {
				$module['section'] = $module['type'] === 'element' ? 'elements' : 'extras';
			}

			$this->modules[ $module['id'] ] = $module;
		}

		uasort(
			$this->modules,
			function ( $a, $b ) {
				return strcasecmp( $a['title'], $b['title'] );
			}
		);
	}

	/**
	 * Every module found on disk.
	 */
	public function all() {
		return $this->modules;
	}

	/**
	 * Saved on/off state, falling back to each module default.
	 */
	public function get_states() {
		$saved  = (array) get_option( SBBT_OPTION, [] );
		$states = [];

		foreach ( $this->modules as $id => $module ) {
			$states[ $id ] = array_key_exists( $id, $saved )
				? (bool) $saved[ $id ]
				: (bool) $module['default'];
		}

		return $states;
	}

	public function is_enabled( $id ) {
		$states = $this->get_states();

		return ! empty( $states[ $id ] ) && ! $this->missing( $id );
	}

	/**
	 * A module's card setting: the saved value, or its default.
	 */
	public function setting( $id, $key ) {
		$saved = (array) get_option( self::SETTINGS_OPTION, [] );

		if ( isset( $saved[ $id ] ) && is_array( $saved[ $id ] ) && array_key_exists( $key, $saved[ $id ] ) ) {
			return $saved[ $id ][ $key ];
		}

		return isset( $this->modules[ $id ]['settings'][ $key ]['default'] ) ? $this->modules[ $id ]['settings'][ $key ]['default'] : null;
	}

	/**
	 * Clean a submitted card setting so only valid values are ever saved.
	 */
	public function sanitize_setting( $field, $value ) {
		$type    = isset( $field['type'] ) ? $field['type'] : 'text';
		$default = isset( $field['default'] ) ? $field['default'] : '';

		switch ( $type ) {
			case 'color':
				$value = is_string( $value ) ? trim( $value ) : '';

				if ( $value === '' ) {
					return '';
				}

				$clean = self::sanitize_css_colour( $value );

				return $clean !== '' ? $clean : $default;

			case 'switch':
			case 'checkbox':
				return empty( $value ) ? 0 : 1;

			case 'number':
				if ( ! is_numeric( $value ) ) {
					return $default;
				}

				$number = $value + 0;

				if ( isset( $field['min'] ) ) {
					$number = max( $field['min'], $number );
				}

				if ( isset( $field['max'] ) ) {
					$number = min( $field['max'], $number );
				}

				return $number;

			case 'select':
				$value = is_scalar( $value ) ? (string) $value : '';

				return isset( $field['options'][ $value ] ) ? $value : $default;

			default:
				return is_scalar( $value ) ? sanitize_text_field( (string) $value ) : $default;
		}
	}

	/**
	 * A safe CSS colour: hex, rgb()/hsl(), or a variable like var(--primary) or var(--primary, #fff).
	 * Returns '' for anything else, so nothing unexpected reaches a style rule.
	 */
	public static function sanitize_css_colour( $value ) {
		$value = is_string( $value ) ? trim( $value ) : '';

		if ( $value === '' ) {
			return '';
		}

		$hex  = '#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{4}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})';
		$func = '(?:rgba?|hsla?)\(\s*[0-9.%,\s\/deg-]+\)';
		$var  = 'var\(\s*--[A-Za-z0-9_-]+\s*\)';

		if ( preg_match( '/^' . $hex . '$/', $value ) ) {
			return strtolower( $value );
		}

		if ( preg_match( '/^' . $func . '$/i', $value ) ) {
			return $value;
		}

		if ( preg_match( '/^var\(\s*--[A-Za-z0-9_-]+\s*(?:,\s*(?:' . $hex . '|' . $func . '|' . $var . '|[a-zA-Z]+)\s*)?\)$/i', $value ) ) {
			return preg_replace( '/\s+/', ' ', $value );
		}

		return '';
	}

	/**
	 * Plugins a module can depend on. A module lists the keys it needs in
	 * 'requires' in its module.php, e.g. 'requires' => [ 'acf' ].
	 */
	private function dependencies() {
		return (array) apply_filters(
			'sbbt/dependencies',
			[
				'acf' => [
					'label'  => 'Advanced Custom Fields',
					'active' => function () {
						if ( ! class_exists( 'ACF' ) ) {
						return false;
					}

					/**
					 * Advanced Themer bundles its own copy and loads it when the standalone
					 * plugin is off. That copy hides the ACF menu and trails the current
					 * release, so it does not count as ACF being available.
					 */
					require_once SBBT_PATH . 'includes/class-sbbt-acf-source.php';

					return ! SBBT_Acf_Source::is_bundled();
					},
				],
			]
		);
	}

	/**
	 * Names of anything this module needs that is missing on this site.
	 * A module with something missing never loads, whatever its switch says.
	 */
	public function missing( $id ) {
		if ( empty( $this->modules[ $id ]['requires'] ) ) {
			return [];
		}

		$dependencies = $this->dependencies();
		$missing      = [];

		foreach ( (array) $this->modules[ $id ]['requires'] as $key ) {
			if ( ! isset( $dependencies[ $key ] ) ) {
				continue;
			}

			if ( ! isset( $this->dependency_state[ $key ] ) ) {
				$this->dependency_state[ $key ] = (bool) call_user_func( $dependencies[ $key ]['active'] );
			}

			if ( ! $this->dependency_state[ $key ] ) {
				$missing[] = $dependencies[ $key ]['label'];
			}
		}

		return $missing;
	}

	/**
	 * Modules that are switched on.
	 */
	public function enabled() {
		return array_filter(
			$this->modules,
			function ( $module ) {
				return $this->is_enabled( $module['id'] );
			}
		);
	}

	/**
	 * Register Bricks elements for enabled modules.
	 */
	public function register_elements() {
		if ( ! class_exists( '\Bricks\Elements' ) ) {
			return;
		}

		foreach ( $this->enabled() as $module ) {
			if ( empty( $module['element_file'] ) ) {
				continue;
			}

			\Bricks\Elements::register_element(
				$module['path'] . $module['element_file'],
				$module['element_name'] ?? '',
				$module['element_class'] ?? ''
			);
		}
	}

	/**
	 * Let each enabled module register its own scripts and styles.
	 */
	public function register_assets() {
		foreach ( $this->enabled() as $module ) {
			if ( empty( $module['assets'] ) || ! is_callable( $module['assets'] ) ) {
				continue;
			}

			call_user_func( $module['assets'], $module );
		}
	}

	/**
	 * Run the boot callback of each enabled module, for tweaks that are not elements.
	 */
	private function run_module_hooks() {
		foreach ( $this->enabled() as $module ) {
			$this->active[ $module['id'] ] = true;

			if ( empty( $module['boot'] ) || ! is_callable( $module['boot'] ) ) {
				continue;
			}

			/**
			 * A broken module should never take the site down with it, so a fatal
			 * inside one is caught, logged and skipped. Everything else carries on.
			 */
			try {
				call_user_func( $module['boot'], $module );
			} catch ( \Throwable $e ) {
				unset( $this->active[ $module['id'] ] );

				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( 'SBBT: module ' . $module['id'] . ' failed to load. ' . $e->getMessage() );
				}
			}
		}
	}
}