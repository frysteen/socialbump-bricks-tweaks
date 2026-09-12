<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return [
	'id'          => 'fix-acf-cpt-svg-icons',
	'title'       => __( 'Fix ACF CPT SVG Icons', 'sb-bricks-tweaks' ),
	'description' => __( 'Makes SVG menu icons on post types created in ACF behave like the other admin menu icons: the same size, and they change colour on hover and when active. Menu icons from other plugins are left alone.', 'sb-bricks-tweaks' ),
	'type'        => 'tweak',
	'section'     => 'extras',
	'default'     => false,
	'requires'    => [ 'acf' ],

	'settings'    => [
		'colour' => [
			'type'        => 'color',
			'label'       => __( 'Icon colour', 'sb-bricks-tweaks' ),
			'default'     => '',
			'placeholder' => '#fff or var(--primary)',
		],
	],

	'boot' => function ( $module ) {
		require_once $module['path'] . 'class-sbbt-acf-svg-icons.php';
		SBBT_Acf_Svg_Icons::boot();
	},
];