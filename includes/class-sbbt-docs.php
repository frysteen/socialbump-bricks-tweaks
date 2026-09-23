<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The plugin's own notes, on the Publishing page.
 *
 * Every plugin carries a docs/context.md written for whoever works on it next,
 * which in practice is a fresh chat with no memory of how any of it came about.
 * It ships with the plugin, so it reaches every site.
 *
 * Editing happens here on the hub, because this is the copy that gets published.
 * The block shared between the three plugins is compared against the others, so
 * drift is noticed rather than discovered months later.
 */
class SBBT_Docs {

	const START = '<!-- shared:start -->';
	const END   = '<!-- shared:end -->';

	public static function boot() {
		add_action( 'admin_post_sbbt_save_docs', [ __CLASS__, 'save' ] );

		// The Publishing page draws whatever hooks this.
		add_action( 'sbbt_settings_after', [ __CLASS__, 'render' ] );
	}

	private static function path() {
		return SBBT_PATH . 'docs/context.md';
	}

	/** The shared block, for comparing against the other plugins. */
	public static function shared( $file = '' ) {
		$file = $file !== '' ? $file : self::path();

		if ( ! is_readable( $file ) ) {
			return '';
		}

		$text  = (string) file_get_contents( $file );
		$start = strpos( $text, self::START );
		$end   = strpos( $text, self::END );

		if ( $start === false || $end === false || $end < $start ) {
			return '';
		}

		return substr( $text, $start, $end - $start );
	}

	/** Which of the other plugins say something different. */
	private static function drifted() {
		$ours  = self::shared();
		$other = [
			'Bricks Tweaks' => 'socialbump-bricks-tweaks',
			'Site Kit'      => 'socialbump-site-kit',
			'SEO for AI'    => 'socialbump-ai-knowledge-exporter',
		];
		$out = [];

		if ( $ours === '' ) {
			return $out;
		}

		foreach ( $other as $name => $folder ) {
			$file = WP_PLUGIN_DIR . '/' . $folder . '/docs/context.md';

			if ( $file === self::path() || ! is_readable( $file ) ) {
				continue;
			}

			if ( self::shared( $file ) !== $ours ) {
				$out[] = $name;
			}
		}

		return $out;
	}

	public static function save() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to do that.', 'sb-bricks-tweaks' ) );
		}

		check_admin_referer( 'sbbt_save_docs' );

		$text = isset( $_POST['sbbt_docs'] ) ? (string) wp_unslash( $_POST['sbbt_docs'] ) : '';
		$file = self::path();

		if ( ! is_dir( dirname( $file ) ) ) {
			wp_mkdir_p( dirname( $file ) );
		}

		if ( trim( $text ) !== '' ) {
			file_put_contents( $file, $text ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		}

		wp_safe_redirect( admin_url( 'admin.php?page=sb-bricks-tweaks-publishing&docs=saved' ) );
		exit;
	}

	public static function render() {
		$file = self::path();
		$text = is_readable( $file ) ? (string) file_get_contents( $file ) : '';
		$gone = self::drifted();
		$q    = chr( 34 );

		echo '<section class=' . $q . 'sbbt-section' . $q . '>';
		echo '<div class=' . $q . 'sbbt-section__head' . $q . '><h2>' . esc_html__( 'Notes for next time', 'sb-bricks-tweaks' ) . '</h2>';
		echo '<p>' . esc_html__( 'How this plugin works, written for whoever picks it up next. Published with the plugin, so keep it accurate and keep it public friendly.', 'sb-bricks-tweaks' ) . '</p></div>';
		echo '<div class=' . $q . 'sbbt-section__body' . $q . '>';

		if ( isset( $_GET['docs'] ) ) {
			echo '<div class=' . $q . 'notice notice-success inline' . $q . '><p>' . esc_html__( 'Notes saved.', 'sb-bricks-tweaks' ) . '</p></div>';
		}

		if ( $gone ) {
			echo '<div class=' . $q . 'notice notice-warning inline' . $q . '><p>';
			/* translators: %s: plugin names */
			echo esc_html( sprintf( __( 'The shared part of these notes no longer matches %s. Copy the block between the shared markers across so all three say the same thing.', 'sb-bricks-tweaks' ), implode( ' and ', $gone ) ) );
			echo '</p></div>';
		}

		$prompt  = 'You are picking up work on SocialBUMP Bricks Tweaks, a WordPress plugin. ';
		$prompt .= 'Everything is developed on the hub, plugins.socialbump.com.au, which you reach through its Novamira MCP connector. ';
		$prompt .= 'Before changing anything, read wp-content/plugins/socialbump-bricks-tweaks/docs/context.md on the hub. ';
		$prompt .= 'It explains what the plugin does, how it is built, the conventions it shares with the other two SocialBUMP plugins, and the mistakes already made and fixed. ';
		$prompt .= 'Keep that file current: when you change how something works or learn something the hard way, write it there in the same session. ';
		$prompt .= 'Tell me what you have read before you start. ';
		$prompt .= 'And before you finish, or any time I say we are done, go back over what we changed and bring that file up to date, then tell me exactly what you added or corrected in it. ';
		$prompt .= 'If nothing in it needed changing, say so plainly rather than saying nothing.';

		echo '<h3 class=' . $q . 'sbbt-docs__heading' . $q . '>' . esc_html__( 'Starting a new chat', 'sb-bricks-tweaks' ) . '</h3>';
		echo '<p class=' . $q . 'description' . $q . '>' . esc_html__( 'Copy this in as the first message, so the chat knows where to look.', 'sb-bricks-tweaks' ) . '</p>';
		echo '<textarea class=' . $q . 'large-text code sbbt-docs__prompt' . $q . ' rows=' . $q . '5' . $q . ' readonly onclick=' . $q . 'this.select();' . $q . '>' . esc_textarea( $prompt ) . '</textarea>';

		/**
		 * The second prompt: converting a site that is still on the old names.
		 *
		 * Kept here rather than in the notes because it is needed at the moment a
		 * forgotten site turns up, months after the work was done. Remove it once
		 * every site has been converted and the fallbacks come out.
		 */
		$convert  = 'I have found another site running SocialBUMP Bricks Tweaks that has not been converted to the new naming yet. Help me convert it. ';
		$convert .= 'Work through its Novamira MCP connector, which I will enable. Do not change anything until you have surveyed it and I have said go. ';
		$convert .= 'Background: in September 2026 Bricks Tweaks 1.1.0 renamed everything it saves. Two kinds of name changed and they are handled differently. ';
		$convert .= 'The options are converted automatically by SBBT_Convert the first time the site loads after updating: sbbt_modules becomes sb_tweaks_bricks_features, sbbt_module_settings becomes sb_tweaks_bricks_settings, sbbt_groups becomes sb_tweaks_bricks_groups, and the per user card order under the socialbump_cards user meta gains an sb_tweaks_bricks_groups entry. Nothing old is deleted and it all gets backed up into sb_tweaks_bricks_backup. ';
		$convert .= 'The names saved inside the pages are NOT converted by the plugin and are the part you have to do by hand, per site, with a script. They are: ';
		$convert .= 'condition keys socialbump_acf_relationship to sb_bricks_acf_relationship, socialbump_acf_repeater to sb_bricks_acf_repeater, socialbump_bricks_content to sb_bricks_bricks_content, socialbump_post_type to sb_bricks_post_type, socialbump_woo_archive_display to sb_bricks_woo_archive_display; ';
		$convert .= 'the carousel element name sb-image-carousel to sb-bricks-image-carousel; ';
		$convert .= 'loop settings sbbtRelationshipOrder to sbBricksRelationshipOrder, socialbumpRepeaterOrder to sbBricksRepeaterOrder, socialbumpRepeaterOrderField to sbBricksRepeaterOrderField, sbbtGalleryOrder to sbBricksGalleryOrder, sbbtGalleryOffset to sbBricksGalleryOffset, sbbtGalleryLimit to sbBricksGalleryLimit; ';
		$convert .= 'and the gallery loop query type prefix sbbt_gallery_ to sb_bricks_gallery_. ';
		$convert .= 'All of that lives in postmeta rows whose meta_key starts with _bricks, which means _bricks_page_content_2, _bricks_page_header_2, _bricks_page_footer_2 and _bricks_template_settings, on pages, on bricks_template posts, and on revisions. Convert the revisions too, or restoring one brings an old name back. ';
		$convert .= 'Condition keys sit in the element settings under _conditions, which is a list of lists of rules, each with key, compare and value: rewrite only the key. The carousel is the element name field. The loop settings are plain keys in the element settings, so rebuild the array to keep their position. The gallery query type is settings query objectType. ';
		$convert .= 'Leave alone anything called dynamic_data, which is Bricks own condition type, and any query type starting acf_, which is Bricks own ACF query loop. Our loop ordering setting sits ON those acf_ loops, so the setting key is renamed while the query type is not. ';
		$convert .= 'The order of work matters. First, survey the site read only and report to me: plugin versions, the current sbbt_ option values, the card meta, and every old name found in page data with counts split between live posts and revisions, naming the templates and pages. ';
		$convert .= 'Second, back up every meta row that contains an old name, byte for byte, into one option called sb_bricks_premigration_backup, along with the sbbt_ and sbsk_ options and the card meta. ';
		$convert .= 'Third, I update the plugin on that site. You do not. Wait for me to say it is done. ';
		$convert .= 'Fourth, check the automatic options conversion landed by comparing what SBBT_Convert wrote against the backup you took. ';
		$convert .= 'Fifth, if the site uses the Image Carousel, convert one page that has it first, on its own, and let me look at the front end before you do the rest. It is the only change that renders nothing at all when it goes wrong. ';
		$convert .= 'Sixth, convert the remaining rows, then prove it: zero old names left anywhere, the expected new names present with the counts matching the survey, every touched row still unserialising into an array, and the affected pages returning 200 with no fatal. ';
		$convert .= 'Things that have caught us out before. Do not rely on re-saving a page in the builder to convert it: the builder binds controls by key, so an old key shows as an empty setting and a save can quietly drop the value. Convert the data first, then edit. ';
		$convert .= 'An asset optimiser such as LiteSpeed combines CSS and JS, so the carousel stylesheet and script will not appear by filename in the page source even when they are loading: look for the rendered markup, brxe-sb-bricks-image-carousel and splide__slide, instead of the file names. ';
		$convert .= 'Fix ACF CPT SVG Icons moved out of Bricks Tweaks into Site Kit, under Admin Settings. If it was switched on before the update, tell me, because it will need switching on again over there and nothing carries it across. ';
		$convert .= 'The old names still work after the update, because the conditions keep an alias, the carousel keeps its old name registered through a class marked deprecated, and the loop settings fall back to the old keys. That is insurance, not a reason to skip the conversion. ';
		$convert .= 'Never assume a write worked. Read it back in a fresh call and show me the numbers.';

		echo '<h3 class=' . $q . 'sbbt-docs__heading' . $q . '>' . esc_html__( 'Converting another site to the new naming', 'sb-bricks-tweaks' ) . '</h3>';
		echo '<p class=' . $q . 'description' . $q . '>' . esc_html__( 'For a site still on the old names. Copy this in as the first message of a new chat, then enable that site connector.', 'sb-bricks-tweaks' ) . '</p>';
		echo '<textarea class=' . $q . 'large-text code sbbt-docs__prompt' . $q . ' rows=' . $q . '8' . $q . ' readonly onclick=' . $q . 'this.select();' . $q . '>' . esc_textarea( $convert ) . '</textarea>';
		echo '<h3 class=' . $q . 'sbbt-docs__heading' . $q . '>' . esc_html__( 'The notes themselves', 'sb-bricks-tweaks' ) . '</h3>';

		echo '<form method=' . $q . 'post' . $q . ' action=' . $q . esc_url( admin_url( 'admin-post.php' ) ) . $q . '>';
		echo '<input type=' . $q . 'hidden' . $q . ' name=' . $q . 'action' . $q . ' value=' . $q . 'sbbt_save_docs' . $q . '>';
		wp_nonce_field( 'sbbt_save_docs' );
		echo '<textarea name=' . $q . 'sbbt_docs' . $q . ' rows=' . $q . '18' . $q . ' class=' . $q . 'large-text code sbbt-docs' . $q . ' spellcheck=' . $q . 'false' . $q . '>' . esc_textarea( $text ) . '</textarea>';
		echo '<p><button type=' . $q . 'submit' . $q . ' class=' . $q . 'button' . $q . '>' . esc_html__( 'Save notes', 'sb-bricks-tweaks' ) . '</button></p>';
		echo '</form></div></section>';
	}
}
