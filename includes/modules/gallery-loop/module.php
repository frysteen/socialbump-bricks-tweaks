<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return [
	'id'          => 'gallery-loop',
	'title'       => __( 'ACF Gallery Loop', 'sb-bricks-tweaks' ),
	'description' => __( 'Adds each ACF gallery field to the query loop Type list, so you can loop over its images and build the markup yourself. Works with any return format, and comes with ordering options.', 'sb-bricks-tweaks' ),
	'type'        => 'tweak',
	'section'     => 'extras',
	'default'     => false,
	'requires'    => [ 'acf' ],

	'boot' => function ( $module ) {
		require_once $module['path'] . 'class-sbbt-gallery-loop.php';
		SBBT_Gallery_Loop::boot();
	},
];