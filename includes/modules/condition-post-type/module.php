<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return [
	'id'          => 'condition-post-type',
	'title'       => __( 'Post Type', 'sb-bricks-tweaks' ),
	'description' => __( 'Show or hide an element depending on the post type, so one shared template can handle Pages, Posts and custom post types.', 'sb-bricks-tweaks' ),
	'type'        => 'condition',
	'section'     => 'conditions',
	'default'     => false,

	'boot' => function ( $module ) {
		require_once SBBT_PATH . 'includes/class-sbbt-conditions.php';
		SBBT_Conditions::register( include $module['path'] . 'condition.php' );
	},
];
