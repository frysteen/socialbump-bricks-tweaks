<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return [
	'id'          => 'acf-sorting',
	'title'       => __( 'ACF Loop Sorting', 'sb-bricks-tweaks' ),
	'description' => __( 'Adds an Order setting to ACF query loops, so rows can be shown reversed, sorted or at random. The order saved in ACF never changes.', 'sb-bricks-tweaks' ),
	'type'        => 'tweak',
	'section'     => 'extras',
	'default'     => false,
	'requires'    => [ 'acf' ],

	'settings'    => [
		'repeater'     => [
			'type'    => 'switch',
			'label'   => __( 'Repeater loops', 'sb-bricks-tweaks' ),
			'default' => 1,
		],
		'relationship' => [
			'type'    => 'switch',
			'label'   => __( 'Relationship loops', 'sb-bricks-tweaks' ),
			'default' => 1,
		],
	],

	'boot' => function ( $module ) {
		$settings = SBBT_Modules::instance();

		if ( $settings->setting( 'acf-sorting', 'repeater' ) ) {
			require_once $module['path'] . 'class-sbbt-repeater-ordering.php';
			SBBT_Repeater_Ordering::boot();
		}

		if ( $settings->setting( 'acf-sorting', 'relationship' ) ) {
			require_once $module['path'] . 'class-sbbt-relationship-ordering.php';
			SBBT_Relationship_Ordering::boot();
		}
	},
];