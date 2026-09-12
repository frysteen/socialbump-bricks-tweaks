<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return [
	'id'          => 'condition-bricks-content',
	'title'       => __( 'Bricks Content', 'sb-bricks-tweaks' ),
	'description' => __( 'Show or hide an element depending on whether the post was built with Bricks. Handy for falling back to normal WordPress content.', 'sb-bricks-tweaks' ),
	'type'        => 'condition',
	'section'     => 'conditions',
	'default'     => false,

	'boot' => function ( $module ) {
		require_once SBBT_PATH . 'includes/class-sbbt-conditions.php';
		SBBT_Conditions::register( include $module['path'] . 'condition.php' );
	},
];
