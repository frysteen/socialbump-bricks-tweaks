<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return [
	'id'          => 'repeater-ordering',
	'title'       => __( 'ACF Repeater Ordering', 'sb-bricks-tweaks' ),
	'description' => __( 'Adds a Repeater order setting to Container, Block and Div query loops. Show ACF repeater rows reversed, A to Z, by number, by date or at random. The order saved in ACF never changes.', 'sb-bricks-tweaks' ),
	'type'        => 'tweak',
	'section'     => 'extras',
	'default'     => false,
	'requires'    => [ 'acf' ],

	'boot' => function ( $module ) {
		require_once $module['path'] . 'class-sbbt-repeater-ordering.php';
		SBBT_Repeater_Ordering::boot();
	},
];