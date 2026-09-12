<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return [
	'id'          => 'condition-acf-repeater',
	'title'       => __( 'ACF Repeater', 'sb-bricks-tweaks' ),
	'description' => __( 'Show or hide an element depending on whether an ACF repeater has rows. Pick the repeater from a list.', 'sb-bricks-tweaks' ),
	'type'        => 'condition',
	'section'     => 'conditions',
	'default'     => false,
	'requires'    => [ 'acf' ],

	'boot' => function ( $module ) {
		require_once SBBT_PATH . 'includes/class-sbbt-conditions.php';
		SBBT_Conditions::register( include $module['path'] . 'condition.php' );
	},
];
