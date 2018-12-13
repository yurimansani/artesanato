<?php

/**
 * Function describe for Bulk child
 * 
 * @package bulk_shop
 */
function bulk_shop_enqueue_styles() {
	/* bulk-stylesheet <- Handle in parent theme */
	wp_enqueue_style( 'bulk-stylesheet', get_template_directory_uri() . '/style.css', array( 'bootstrap' ) );
	wp_enqueue_style( 'bulk-shop-style', get_stylesheet_uri(), array( 'bulk-stylesheet' ) );
}

add_action( 'wp_enqueue_scripts', 'bulk_shop_enqueue_styles' );

// Remove WooCommerce category description
remove_action( 'woocommerce_archive_description', 'woocommerce_taxonomy_archive_description', 10 );

// Remove WooCommerce shop/category titles
add_filter( 'woocommerce_show_page_title', 'bulk_shop_hide_page_title' );

function bulk_shop_hide_page_title() {
	return false;
}

function bulk_shop_header_shop_page() {

	if ( class_exists( 'woocommerce' ) ) {
		// display featured image on shop/category pages
		if ( is_shop() || is_product_category() ) {
			if ( is_shop() ) {
				$shop_id = get_option( 'woocommerce_shop_page_id' );
			} else if ( is_product_category() ) {
				global $wp_query;
				$term	 = $wp_query->queried_object;
				$id		 = $wp_query->queried_object_id;
				$meta	 = get_term_meta( $id, 'thumbnail_id', true );
			}
			?>
			<div class="top-header text-center">
				<?php if ( is_shop() && has_post_thumbnail( $shop_id ) ) : ?>
					<div class="single-image">
						<?php echo get_the_post_thumbnail( $shop_id, 'full' ); ?>
					</div>
				<?php endif; ?>
				<?php if ( is_product_category() && $meta != '' ) : ?>
					<div class="single-image">
						<?php echo wp_get_attachment_image( $meta, 'full' ); ?>
					</div>
				<?php endif; ?>
				<header class="header-title container">
					<h1 class="page-header">                                
						<?php if ( is_shop() ) { ?>
							<?php echo get_the_title( esc_html( $shop_id ) ); ?>
						<?php } else if ( is_product_category() ) { ?>
							<?php echo esc_html( $term->name ); ?>
						<?php } ?>                    
					</h1>
					<?php if ( is_product_category() ) { ?>
						<div class="category-description">                                
							<?php echo wp_kses_post( $term->description ); ?>
						</div>  
					<?php } ?> 
				</header>
			</div>
			<?php
		}
	}
}

if ( class_exists( 'woocommerce' ) ) {

	/*
	 * WooCommerce cart
	 */
	if ( !function_exists( 'bulk_shop_opt_cart_link' ) ) {

		function bulk_shop_opt_cart_link() {
			?>	
			<a class="cart-contents" href="<?php echo esc_url( wc_get_cart_url() ); ?>" title="<?php esc_attr_e( 'View your shopping cart', 'bulk-shop' ); ?>">
				<i class="fa fa-shopping-cart"></i>
				<span class="amount-number simple-counter"> 
					<?php echo absint( WC()->cart->get_cart_contents_count() ) ?>
				</span>
			</a>
			<?php
		}

	}

	if ( !function_exists( 'bulk_shop_opt_cart' ) ) {

		function bulk_shop_opt_cart() {
			?> 
			<?php bulk_shop_opt_cart_link(); ?>
			<ul class="site-header-cart text-center list-unstyled">
				<li>
					<?php the_widget( 'WC_Widget_Cart', 'title=' ); ?>
				</li>
			</ul>
			<?php
		}

	}


	if ( !function_exists( 'bulk_shop_opt_header_add_to_cart_fragment' ) ) {

		add_filter( 'woocommerce_add_to_cart_fragments', 'bulk_shop_opt_add_to_cart_fragment' );

		function bulk_shop_opt_add_to_cart_fragment( $fragments ) {
			ob_start();
			bulk_shop_opt_cart_link();
			$fragments[ '.cart-contents' ] = ob_get_clean();
			return $fragments;
		}

	}
}

/**
 * Remove parent footer credits
 */
function bulk_shop_remove_parent_footer() {
	remove_action( 'bulk_generate_footer', 'bulk_generate_construct_footer' );
}

add_action( 'init', 'bulk_shop_remove_parent_footer' );

/**
 * Build footer
 */
add_action( 'bulk_generate_footer', 'bulk_shop_generate_construct_footer' );

function bulk_shop_generate_construct_footer() {
	?>
	<p class="footer-credits-text text-center">
		<?php
		/* translators: %s: link to https://wordpress.org/ */
		printf( esc_html__( 'Proudly powered by %s', 'bulk-shop' ), '<a href="' . esc_url( __( 'https://wordpress.org/', 'bulk-shop' ) ) . '">WordPress</a>' );
		?>
		<span class="sep"> | </span>
		<?php
		/* translators: %1$s: link to theme page */
		printf( esc_html__( 'Theme: %1$s', 'bulk-shop' ), '<a href="https://themes4wp.com/theme/bulk-shop/">Bulk Shop</a>' );
		?>
	</p> 
	<?php
}

add_action( 'after_setup_theme', 'bulk_shop_setup', 99 );

/**
 * Global functions
 */
function bulk_shop_setup() {

	// Recommend plugins.
	add_theme_support( 'recommend-plugins', array(
		'woocommerce'	 => array(
			'name'				 => 'WooCommerce',
			'active_filename'	 => 'woocommerce/woocommerce.php',
			/* translators: %1$s plugin name string */
			'description'		 => sprintf( esc_attr__( 'To enable shop features, please install and activate the %s plugin.', 'bulk-shop' ), '<strong>WooCommerce</strong>' ),
		),
		'elementor'		 => array(
			'name'				 => 'Elementor Page Builder',
			'active_filename'	 => 'elementor/elementor.php',
			/* translators: %1$s plugin name string */
			'description'		 => sprintf( esc_attr__( 'To take full advantage of all the features this theme has to offer, please install and activate the %s plugin.', 'bulk-shop' ), '<strong>Elementor Page Builder</strong>' ),
		),
	) );
}
