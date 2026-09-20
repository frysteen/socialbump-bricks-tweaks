<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Move this plugin's saved settings onto the sb_tweaks_bricks_ names.
 *
 * The options used three different prefixes between them and are about to
 * become part of SocialBUMP Tweaks, where every module stores its settings as
 * sb_tweaks_<module>_. Converting here rather than in the merged plugin means
 * the merge inherits clean data and needs no converter of its own.
 *
 * Everything old is copied into one option before anything is written, so a
 * single call puts it all back. Nothing old is deleted: it costs a few rows
 * and it is the difference between a mistake being annoying and being final.
 *
 * Runs once, and records the scheme it ran. Bump SCHEME if the names ever move
 * again and it runs once more.
 */
class SBBT_Convert {

	const SCHEME = 1;
	const FLAG   = 'sb_tweaks_bricks_scheme';
	const BACKUP = 'sb_tweaks_bricks_backup';

	/** Old name => new name. */
	public static function options() {
		return [
			'sbbt_modules'         => 'sb_tweaks_bricks_features',
			'sbbt_module_settings' => 'sb_tweaks_bricks_settings',
			'sbbt_groups'          => 'sb_tweaks_bricks_groups',
		];
	}

	/** The card order and collapsed state, kept per user, keyed by page. */
	const CARDS_META = 'socialbump_cards';
	const CARDS_OLD  = 'sbbt_groups';
	const CARDS_NEW  = 'sb_tweaks_bricks_groups';

	public static function maybe_run() {
		if ( (int) get_option( self::FLAG ) >= self::SCHEME ) {
			return;
		}

		self::run();
	}

	public static function run() {
		$backup = [ 'when' => time(), 'options' => [], 'cards' => [] ];

		foreach ( self::options() as $old => $new ) {
			$value = get_option( $old, null );

			if ( $value === null || $value === false ) {
				continue;
			}

			$backup['options'][ $old ] = $value;

			// Never write over a new value that is already there: a second run must
			// not undo whatever has been saved since the first one.
			if ( get_option( $new, null ) === null ) {
				update_option( $new, $value );
			}
		}

		self::move_cards( $backup );

		update_option( self::BACKUP, $backup, false );
		update_option( self::FLAG, self::SCHEME, false );
	}

	/** Each user's card arrangement for this plugin's Features page. */
	private static function move_cards( &$backup ) {
		global $wpdb;

		$rows = $wpdb->get_results(
			$wpdb->prepare( "SELECT user_id, meta_value FROM {$wpdb->usermeta} WHERE meta_key = %s", self::CARDS_META )
		);

		foreach ( (array) $rows as $row ) {
			$state = maybe_unserialize( $row->meta_value );

			if ( ! is_array( $state ) || ! isset( $state[ self::CARDS_OLD ] ) ) {
				continue;
			}

			$backup['cards'][ (int) $row->user_id ] = $state;

			if ( ! isset( $state[ self::CARDS_NEW ] ) ) {
				$state[ self::CARDS_NEW ] = $state[ self::CARDS_OLD ];
			}

			update_user_meta( (int) $row->user_id, self::CARDS_META, $state );
		}
	}

	/** What the conversion did, for checking it afterwards. */
	public static function report() {
		$out = [ 'scheme' => (int) get_option( self::FLAG ), 'options' => [], 'cards' => 0 ];

		foreach ( self::options() as $old => $new ) {
			$out['options'][ $new ] = [
				'old still there' => get_option( $old, null ) !== null,
				'new written'     => get_option( $new, null ) !== null,
				'identical'       => get_option( $old, null ) === get_option( $new, null ),
			];
		}

		$backup = (array) get_option( self::BACKUP, [] );
		$out['cards'] = isset( $backup['cards'] ) ? count( (array) $backup['cards'] ) : 0;

		return $out;
	}
}