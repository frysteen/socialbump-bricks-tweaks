<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return [
	'id'            => 'image-carousel',
	'title'         => __( 'Image Carousel', 'sb-bricks-tweaks' ),
	'description'   => __( 'An SEO friendly image carousel. Real img tags with alt text, drag ordering, ACF gallery support, breakpoint controls, lightbox and continuous scroll.', 'sb-bricks-tweaks' ),
	'type'          => 'element',
	'default'       => true,
	'element_file'  => 'class-element-image-carousel.php',
	'element_name'  => 'sb-image-carousel',
	'element_class' => 'SB_Element_Image_Carousel',

	'assets' => function ( $module ) {
		$ver = function ( $relative ) use ( $module ) {
			$file = $module['path'] . $relative;

			return file_exists( $file ) ? (string) filemtime( $file ) : SBBT_VERSION;
		};

		wp_register_style(
			'sb-carousel',
			$module['url'] . 'assets/css/sb-carousel.css',
			[],
			$ver( 'assets/css/sb-carousel.css' )
		);

		wp_register_script(
			'sb-splide-auto-scroll',
			$module['url'] . 'assets/js/splide-extension-auto-scroll.min.js',
			[ 'bricks-splide' ],
			'0.5.3',
			true
		);

		wp_register_script(
			'sb-carousel',
			$module['url'] . 'assets/js/sb-carousel.js',
			[ 'bricks-splide' ],
			$ver( 'assets/js/sb-carousel.js' ),
			true
		);
	},
];