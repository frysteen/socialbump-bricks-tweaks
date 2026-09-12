<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Bricks Content: has / has no saved Bricks content.
 *
 * Handy for showing the normal WordPress content on posts that were never built in Bricks.
 */
return [
	'key'     => 'socialbump_bricks_content',
	'label'   => 'Bricks Content',
	'compare' => [
		'has_content'    => 'Has Bricks content',
		'has_no_content' => 'Has no Bricks content',
	],
	'check'   => function ( $compare, $value, $post_id ) {
		$meta_key = defined( 'BRICKS_DB_PAGE_CONTENT' ) ? BRICKS_DB_PAGE_CONTENT : '_bricks_page_content_2';
		$content  = get_post_meta( $post_id, $meta_key, true );
		$has      = is_array( $content ) ? ! empty( $content ) : trim( (string) $content ) !== '';

		return $compare === 'has_no_content' ? ! $has : $has;
	},
];