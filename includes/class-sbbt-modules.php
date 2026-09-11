<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Finds every module folder, tracks which are switched on, and boots them.
 *
 * To add a module: create includes/modules/<slug>/module.php returning an array.
 * Nothing else in the plugin needs editing.
 */
class SBBT_Modules {

	private static $instance = null;

	/** All discovered modules, keyed by id. */
	private $modules = [];

	/** Modules that are switched on and have booted. */
	private $active = [];

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
					'path'        => trailingslashit( $dir ),
					'url'         => trailingslashit( SBBT_URL . 'includes/modules/' . basename( $dir ) ),
				]
			);

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
		$saved  = get_option( SBBT_OPTION, [] );
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

		return ! empty( $states[ $id ] );
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

			call_user_func( $module['boot'], $module );
		}
	}
}