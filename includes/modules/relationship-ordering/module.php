<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return [
	'id'          => 'relationship-ordering',
	'title'       => __( 'ACF Relationship Ordering', 'sb-bricks-tweaks' ),
	'description' => __( 'Adds an Order setting to relationship and post object query loops: reverse, by title, by date, by menu order or random. The order saved in ACF stays the same.', 'sb-bricks-tweaks' ),
	'type'        => 'tweak',
	'section'     => 'extras',
	'default'     => false,
	'requires'    => [ 'acf' ],

	'boot' => function ( $module ) {
		require_once $module['path'] . 'class-sbbt-relationship-ordering.php';
		SBBT_Relationship_Ordering::boot();
	},
];