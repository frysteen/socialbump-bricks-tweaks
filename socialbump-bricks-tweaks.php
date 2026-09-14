<?php
/**
 * Plugin Name: SocialBUMP Bricks Tweaks
 * Plugin URI:  https://socialbump.com.au
 * Description: A home for SocialBUMP custom Bricks Builder elements and site tweaks. Turn each one on or off under SB Bricks Tweaks.
 * Version:     1.0.1
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

define( 'SBBT_VERSION', '1.0.1' );
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

/**
 * Note a change for the next release.
 *
 * Anything logged here fills in the notes box on the Publishing page, and the
 * list is emptied once a release goes out.
 */
function sbbt_log_change( $text ) {
	$text = trim( wp_strip_all_tags( (string) $text ) );

	if ( $text === '' ) {
		return;
	}

	$list = (array) get_option( 'sbbt_pending_changes', [] );

	if ( in_array( $text, $list, true ) ) {
		return;
	}

	$list[] = $text;

	update_option( 'sbbt_pending_changes', array_slice( $list, -50 ), false );
}

function sbbt_boot() {
	if ( ! sbbt_bricks_active() ) {
		add_action( 'admin_notices', 'sbbt_missing_bricks_notice' );
		return;
	}

	require_once SBBT_PATH . 'includes/class-sbbt-modules.php';
	require_once SBBT_PATH . 'includes/class-socialbump-admin-bar.php';
require_once SBBT_PATH . 'includes/class-socialbump-overview.php';
	require_once SBBT_PATH . 'includes/class-sbbt-settings.php';

	SBBT_Modules::instance()->boot();
	SBBT_Settings::instance()->boot();
	require_once SBBT_PATH . 'includes/class-sbbt-updates.php';
	require_once SBBT_PATH . 'includes/class-sbbt-transfer.php';
	SBBT_Updates::boot();
	SBBT_Transfer::boot();

	if ( sbbt_is_hub() ) {
		require_once SBBT_PATH . 'includes/class-sbbt-release.php';
		SBBT_Release::instance()->boot();

		require_once SBBT_PATH . 'includes/class-sbbt-docs.php';
		SBBT_Docs::boot();
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

/**
 * Make sure the new files are the ones that run.
 *
 * Updating a plugin swaps its files out mid request. If you were on one of its
 * own pages at the time, the page you land on afterwards can still be running
 * the old code, or code caught halfway through being replaced, so its menus
 * never register and the plugin appears to vanish until you go somewhere else.
 *
 * Clearing the compiled copies as soon as the update finishes means the next
 * request reads what is actually on disk.
 */
function sbbt_forget_compiled( $upgrader, $extra ) {
	if ( ! function_exists( 'opcache_invalidate' ) ) {
		return;
	}

	$ours = plugin_basename( SBBT_FILE );
	$mine = isset( $extra['plugins'] ) && in_array( $ours, (array) $extra['plugins'], true );

	// A single update reports the plugin on its own rather than in a list.
	if ( ! $mine && isset( $extra['plugin'] ) && $extra['plugin'] === $ours ) {
		$mine = true;
	}

	if ( ! $mine ) {
		return;
	}

	$files = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( SBBT_PATH, FilesystemIterator::SKIP_DOTS ) );

	foreach ( $files as $file ) {
		if ( $file->getExtension() === 'php' ) {
			@opcache_invalidate( $file->getPathname(), true );
		}
	}
}
add_action( 'upgrader_process_complete', 'sbbt_forget_compiled', 10, 2 );

/**
 * Nothing about publishing belongs on a site that is not the hub.
 *
 * This site is the blueprint new sites are built from, so whatever sits in its
 * database travels with every copy. A GitHub token has no business on a client
 * site, and the release notes waiting to be published are only noise there.
 */
function sbbt_tidy_away_hub_data() {
	if ( sbbt_is_hub() ) {
		return;
	}

	foreach ( [ 'sbbt_github_token', 'sbbt_pending_changes', 'sbbt_latest_release' ] as $option ) {
		if ( get_option( $option ) !== false ) {
			delete_option( $option );
		}
	}
}
add_action( 'admin_init', 'sbbt_tidy_away_hub_data' );
