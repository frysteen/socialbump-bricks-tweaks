<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return [
	'id'          => 'default-wp-editor',
	'title'       => __( 'Default To WP Editor', 'sb-bricks-tweaks' ),
	'description' => __( 'Opens the WordPress editor instead of the Bricks tab on posts with no Bricks content of their own. The Bricks tab is still one click away.', 'sb-bricks-tweaks' ),
	'type'        => 'tweak',
	'section'     => 'extras',
	'default'     => false,

	'boot' => function ( $module ) {
		require_once $module['path'] . 'class-sbbt-default-wp-editor.php';
		SBBT_Default_WP_Editor::boot();
	},
];