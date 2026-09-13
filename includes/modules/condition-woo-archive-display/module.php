<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return [
	'id'          => 'condition-woo-archive-display',
	'title'       => __( 'WooCommerce Archive Display', 'sb-bricks-tweaks' ),
	'description' => __( 'Show or hide an element depending on whether the shop or category archive is set to list products, categories, or both.', 'sb-bricks-tweaks' ),
	'type'        => 'condition',
	'section'     => 'conditions',
	'default'     => false,
	'requires'    => [ 'woocommerce' ],

	'boot' => function ( $module ) {
		require_once SBBT_PATH . 'includes/class-sbbt-conditions.php';
		SBBT_Conditions::register( include $module['path'] . 'condition.php' );
	},
];
