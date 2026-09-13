<?php
/**
 * Plugin Name: SocialBUMP Bricks Tweaks
 * Plugin URI:  https://socialbump.com.au
 * Description: A home for SocialBUMP custom Bricks Builder elements and site tweaks. Turn each one on or off under SB Bricks Tweaks.
 * Version:     1.3.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author:      SocialBUMP
 * Author URI:  https://socialbump.com.au
 * License:     GPL-2.0-or-later
 * Text Domain: sb-bricks-tweaks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SBBT_VERSION', '1.3.0' );
define( 'SBBT_FILE', __FILE__ );
define( 'SBBT_PATH', plugin_dir_path( __FILE__ ) );
define( 'SBBT_URL', plugin_dir_url( __FILE__ ) );
define( 'SBBT_OPTION', 'sbbt_modules' );
define( 'SBBT_SLUG', 'socialbump-bricks-tweaks' );
define( 'SBBT_GITHUB_REPO', 'frysteen/socialbump-bricks-tweaks' );
define( 'SBBT_HUB_HOST', 'bricks.socialbump.com.au' );

/**
 * Updates come from GitHub Releases. A release only counts as an update
 * when it has socialbump-bricks-tweaks.zip attached.
 */
function sbbt_updater() {
	$loader = SBBT_PATH . 'vendor/plugin-update-checker/plugin-update-checker.php';

	if ( ! is_readable( $loader ) ) {
		return;
	}

	require_once $loader;

	if ( ! class_exists( 'YahnisElsts\PluginUpdateChecker\v5\PucFactory' ) ) {
		return;
	}

	try {
		$checker = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
			'https://github.com/' . SBBT_GITHUB_REPO . '/',
			SBBT_FILE,
			SBBT_SLUG
		);

		/**
		 * Some plugins hook plugins_api at the default priority and return false for
		 * every request, not just their own, which wipes out the plugin details this
		 * updater supplies (FooEvents does this). Re-add the details callback later so
		 * the View details screen still works on those sites.
		 */
		$GLOBALS['sbbt_update_checker'] = $checker;

		/**
		 * The plugin icon, for the updates list and the details popup. Plugins outside
		 * the WordPress directory have none unless the update data supplies one.
		 */
		add_filter(
			'puc_request_info_result-' . SBBT_SLUG,
			function ( $info ) {
				if ( is_object( $info ) ) {
					$info->icons = [
						'1x'      => SBBT_URL . 'assets/img/icon-128x128.png',
						'2x'      => SBBT_URL . 'assets/img/icon-256x256.png',
						'default' => SBBT_URL . 'assets/img/icon-256x256.png',
					];
				}

				return $info;
			}
		);

		remove_filter( 'plugins_api', [ $checker, 'injectInfo' ], 20 );
		add_filter( 'plugins_api', [ $checker, 'injectInfo' ], 999, 3 );

		// 2 = Api::REQUIRE_RELEASE_ASSETS. Releases without the zip are ignored.
		$checker->getVcsApi()->enableReleaseAssets( '/^socialbump-bricks-tweaks\.zip$/', 2 );
	} catch ( \Throwable $e ) {
		// Never let the updater take a site down.
	}
}
sbbt_updater();

/**
 * True only on the hub site, where releases are built and published.
 * Define SBBT_IS_HUB in wp-config.php to override.
 */
function sbbt_is_hub() {
	if ( defined( 'SBBT_IS_HUB' ) ) {
		return (bool) SBBT_IS_HUB;
	}

	$host = wp_parse_url( home_url(), PHP_URL_HOST );

	return strtolower( (string) $host ) === SBBT_HUB_HOST;
}

function sbbt_bricks_active() {
	$theme = wp_get_theme();

	if ( $theme->get_template() === 'bricks' ) {
		return true;
	}

	return defined( 'BRICKS_VERSION' );
}

function sbbt_activate() {
	if ( sbbt_bricks_active() ) {
		return;
	}

	deactivate_plugins( plugin_basename( __FILE__ ) );

	wp_die(
		esc_html__( 'SocialBUMP Bricks Tweaks needs the Bricks theme to be active. Activate Bricks first, then try again.', 'sb-bricks-tweaks' ),
		esc_html__( 'Bricks theme required', 'sb-bricks-tweaks' ),
		[ 'back_link' => true ]
	);
}
register_activation_hook( __FILE__, 'sbbt_activate' );

function sbbt_boot() {
	if ( ! sbbt_bricks_active() ) {
		add_action( 'admin_notices', 'sbbt_missing_bricks_notice' );
		return;
	}

	require_once SBBT_PATH . 'includes/class-sbbt-modules.php';
	require_once SBBT_PATH . 'includes/class-sbbt-settings.php';

	SBBT_Modules::instance()->boot();
	SBBT_Settings::instance()->boot();
	require_once SBBT_PATH . 'includes/class-sbbt-updates.php';
	SBBT_Updates::boot();

	if ( sbbt_is_hub() ) {
		require_once SBBT_PATH . 'includes/class-sbbt-release.php';
		SBBT_Release::instance()->boot();
	}
}
add_action( 'plugins_loaded', 'sbbt_boot' );

function sbbt_missing_bricks_notice() {
	if ( ! current_user_can( 'activate_plugins' ) ) {
		return;
	}

	echo '<div class="notice notice-warning"><p><strong>SocialBUMP Bricks Tweaks</strong> ';
	esc_html_e( 'is paused because the Bricks theme is not active.', 'sb-bricks-tweaks' );
	echo '</p></div>';
}

function sbbt_action_links( $links ) {
	if ( sbbt_bricks_active() ) {
		$url = admin_url( 'admin.php?page=sb-bricks-tweaks' );
		array_unshift( $links, '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings', 'sb-bricks-tweaks' ) . '</a>' );
	}

	return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'sbbt_action_links' );