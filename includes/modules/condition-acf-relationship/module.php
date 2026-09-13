<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return [
	'id'          => 'condition-acf-relationship',
	'title'       => __( 'ACF Relationship', 'sb-bricks-tweaks' ),
	'description' => __( 'Show or hide an element depending on whether an ACF relationship or post object field has anything in it.', 'sb-bricks-tweaks' ),
	'type'        => 'condition',
	'section'     => 'conditions',
	'default'     => false,
	'requires'    => [ 'acf' ],

	'boot' => function ( $module ) {
		require_once SBBT_PATH . 'includes/class-sbbt-conditions.php';
		SBBT_Conditions::register( include $module['path'] . 'condition.php' );
	},
];