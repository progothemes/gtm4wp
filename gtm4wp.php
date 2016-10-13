<?php
/**
 * Plugin Name: Google Tag Manager 4 WordPress
 * Plugin URI: https://github.com/progothemes/gtm4wp
 * Description: Add Google Tag Manager to WordPress, with advanced eCommerce DataLayer support for WooCommerce
 * Version: 1.2.1
 * Author: ProGo
 * Author URI: http://www.progo.com
 * Text Domain: gtm4wp
 * License: GPL2
 */

//exit if accessed directly
if(!defined('ABSPATH')) exit;

add_action( 'admin_menu', 'gtm4wp_add_admin_menu' );
add_action( 'admin_init', 'gtm4wp_settings_init' );

// Theme Hooks Alliance actions
add_action( 'wp_head', 'gtm4wp_datalayer_init', 1 );
add_action( 'wp_head', 'gtm4wp_container_output', 2 );
add_action( 'wp_footer', 'gtm4wp_woo_datalayer', 10 );
add_action( 'tha_body_top', 'gtm4wp_noscript_output', 2 );
// And in case no THA...
add_action( 'gtm4wp_render', 'gtm4wp_container_output', 10 );

function gtm4wp_render() {
	do_action( 'gtm4wp_render' ); // use for hooking if tha_body_top not available
}

function gtm4wp_add_admin_menu(  ) {
	add_submenu_page( 'options-general.php', 'GTM 4 WP', 'Google Tag Manager', 'manage_options', 'gtmforwordpress', 'gtm4wp_options_page' );
}

function gtm4wp_settings_init(  ) {
	register_setting( 'pluginPage', 'gtm4wp_settings' );
	add_settings_section(
		'gtm4wp_pluginPage_section',
		__( 'Add Google Tag Manager to WordPress', 'gtm4wp' ),
		'gtm4wp_settings_section_callback',
		'pluginPage'
	);
	add_settings_field(
		'gtm4wp_container_id',
		__( 'Container ID', 'gtm4wp' ),
		'gtm4wp_container_id_render',
		'pluginPage',
		'gtm4wp_pluginPage_section'
	);
	add_settings_field(
		'gtm4wp_brand',
		__( 'Brand', 'gtm4wp' ),
		'gtm4wp_brand_render',
		'pluginPage',
		'gtm4wp_pluginPage_section'
	);
}

function gtm4wp_container_id_render(  ) {
	$options = get_option( 'gtm4wp_settings' );
	?>
	<input type='text' name='gtm4wp_settings[gtm4wp_container_id]' value='<?php echo $options['gtm4wp_container_id']; ?>'>
	<?php
}

function gtm4wp_brand_render(  ) {
	$options = get_option( 'gtm4wp_settings' );
	?>
	<input type='text' name='gtm4wp_settings[gtm4wp_brand]' value='<?php echo $options['gtm4wp_brand']; ?>'>
	<?php
}

function gtm4wp_settings_section_callback(  ) {
	echo '<p>'. __( 'This plugin requires you have access to edit your theme files OR your theme includes a \'tha_body_top\' hook. Per updated Google Tag Manager best practices, there is one section of code which will be injected in the \'wp_head\' area, and another pixel piece which should appear immediately after the opening body tag, which is where \'tha_body_top\' would output.', 'gtm4wp' ) .'</p>';
	echo '<p>'. __( 'For more information, see ', 'gtm4wp' ) .'<a href="https://github.com/progothemes/gtm4wp" target="_blank">https://github.com/progothemes/gtm4wp</a></p>';
}

/**
 * Options Page
 *
 */
function gtm4wp_options_page(  ) {
	?>
	<form action='options.php' method='post'>
		<h2>GTMforWordPress</h2>
		<?php
		settings_fields( 'pluginPage' );
		do_settings_sections( 'pluginPage' );
		submit_button();
		?>
	</form>
	<?php
}

/**
 * GTM Code Output
 *
 */
function gtm4wp_container_output() {
	$options = get_option( 'gtm4wp_settings' );
	$container_id = sanitize_text_field( $options['gtm4wp_container_id'] );
	if ( !empty($container_id) ) {
		printf( "<!-- Google Tag Manager -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','%s');</script>
<!-- End Google Tag Manager -->", $container_id );
	}
}
function gtm4wp_noscript_output() {
 $options = get_option( 'gtm4wp_settings' );
 $container_id = sanitize_text_field( $options['gtm4wp_container_id'] );
 if ( !empty($container_id) ) {
	 printf( "<!-- Google Tag Manager (noscript) -->
<noscript><iframe src=\"https://www.googletagmanager.com/ns.html?id=%s\"
height=\"0\" width=\"0\" style=\"display:none;visibility:hidden\"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->", $container_id );
 }
}

/**
 * GTM dataLayer Init
 *
 */
function gtm4wp_datalayer_init() {
	$options = get_option( 'gtm4wp_settings' );
	printf( "<script>window.dataLayer = window.dataLayer || [];</script>" );
}

/* Enhanced Ecommerce DataLayer
 *
 * This function pushes WooCommerce data into the dataLayer object.
 *
 * https://developers.google.com/tag-manager/enhanced-ecommerce
 */
add_action( 'wp_footer', 'gtm4wp_woo_data_layer' );
function gtm4wp_woo_data_layer() {
	if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
		// Globals and default vars
		global $woocommerce;
		//echo '<pre>'; print_r($woocommerce); echo '</pre>';

		$options = get_option( 'gtm4wp_settings' );
		$brand = sanitize_text_field( $options['gtm4wp_brand'] );

		$str = '';
		$strArr = array();


		// Start Script
		$str = '<script>dataLayer.push(';

		// PRODUCT CATEGORY
		if ( is_product_category() ):
			$category = '';
			$category_slug = get_query_var( 'product_cat' );
			if ( $category_slug ) { $category = get_term_by( 'slug', $category_slug, 'product_cat' ); }
			$i = 1;
			// Get all products in category
			$args = array(
				'post_type'				=> 'product',
				'post_status'			=> 'publish',
				'posts_per_page'		=> -1,
				'product_cat'			=> $category_slug
			);
			$products = new WP_Query($args);
			$str .= '{ \'event\':\'enhanceEcom Product Impression\', \'ecommerce\': { \'impressions\': [';
			while ( $products->have_posts() ) : $products->the_post();
				global $product;
				$strArr[] = sprintf( '{ \'name\': \'%s\', \'id\': \'%s\', \'price\': %f, \'brand\': \'%s\', \'category\': \'%s\', \'list\': \'%s\', \'position\': %d }', get_the_title(), $product->get_sku(), $product->get_price(), $brand, $category->name, $category->name, $i );
				$i++;
			endwhile;
			wp_reset_query();
			$str .= implode(',', $strArr);
			$str .= ']}}';
		endif;

		// PRODUCT pages
		if ( is_product() ):
			$product = new WC_Product( get_the_ID() );
			$variation_skus = '';
			if ( $product->product_type == 'variable' ) { // Get variation Skus
				$variations = $product->get_available_variations();
				$variation_skus = '[{';
				$skusArr = array();
				foreach ($variations as $variation) {
					$skusArr = $variation['sku'];
				}
				$variation_skus .= implode(',', $skusArr);
				$variation_skus .= '}]';
			}
			$terms = get_the_terms( $product->post->ID, 'product_cat' );
			$str .= sprintf( '{\'event\': \'enhanceEcom Product Detail View\', \'ecommerce\': { \'detail\': { \'actionField\': {\'list\': \'%s\'}, \'products\': [{ \'id\': \'%s\', \'name\': \'%s\', \'price\': %f, \'brand\': \'%s\', \'category\': \'%s\', \'variant\': \'%s\' }]}}}', $terms[0]->name, $product->get_sku(), get_the_title(), $product->get_price(), $brand, $terms[0]->name, $variation_skus );
		endif;

		// CART page
		if ( is_cart() ):
			$items = $woocommerce->cart->get_cart();
			$str .= '{ \'event\': \'view Cart\', \'ecommerce\': { \'cart\': { \'products\': [';
			foreach ( $items as $item ) {
				$product_id = $item['variation_id'];
				if ( $product_id ) {
					$product = new WC_Product_Variation( $item['variation_id'] );
				} else {
					$product = new WC_Product( $item['product_id'] );
				}
				$terms = get_the_terms( $product->post->ID, 'product_cat' );
				$strArr[] = sprintf('{ \'name\': \'%s\', \'id\': \'%s\', \'price\': %f, \'brand\': \'%s\', \'category\': \'%s\', \'variant\': \'%s\', \'quantity\': %d }', $product->post->post_title, $product->get_sku(), $product->get_price(), $brand, $terms[0]->name, $product->get_sku(), $item['quantity'] );
			}
			$str .= implode(',', $strArr);
			$str .= ']}}}';
		endif;

		// CHECKOUT page
		if ( is_checkout() ):
			$items = $woocommerce->cart->get_cart();
			$str .= '{ \'event\': \'Product Checkout\', \'ecommerce\': { \'checkout\': { \'products\': [';
			foreach ( $items as $item ) {
				$product_id = $item['variation_id'];
				if ( $product_id ) {
					$product = new WC_Product_Variation( $item['variation_id'] );
				} else {
					$product = new WC_Product( $item['product_id'] );
				}
				$terms = get_the_terms( $product->post->ID, 'product_cat' );
				$strArr[] = sprintf('{ \'name\': \'%s\', \'id\': \'%s\', \'price\': %f, \'brand\': \'%s\', \'category\': \'%s\', \'variant\': \'%s\', \'quantity\': %d }', $product->post->post_title, $product->get_sku(), $product->get_price(), $brand, $terms[0]->name, $product->get_sku(), $item['quantity'] );
			}
			$str .= implode(',', $strArr);
			$str .= ']}}}';
		endif;

		// ORDER RECEIVED page
		if( is_wc_endpoint_url( 'order-received' ) ):
			$order = woo_order_obj();
			$items = $order->get_items();
			$str .= sprintf('{ \'event\': \'enhanceEcom transactionSuccess\', \'ecommerce\': { \'purchase\': { \'actionField\': { \'id\': \'%s\', \'affiliation\': \'%s\', \'revenue\': %f, \'tax\': %f, \'shipping\': %f, \'coupon\': \'%s\' },', $order->get_order_number(), $brand, $order->order_total, $order->order_tax, $order->order_shipping, 'CouponCode' );
			$str .= '\'products\': [';
			foreach ( $items as $item ) {
				$product_id = $item['variation_id'];
				if ( $product_id ) {
					$product = new WC_Product_Variation( $item['variation_id'] );
				} else {
					$product = new WC_Product( $item['product_id'] );
				}
				$terms = get_the_terms( $product->post->ID, 'product_cat' );
				$strArr[] = sprintf('{ \'name\': \'%s\', \'id\': \'%s\', \'price\': %f, \'brand\': \'%s\', \'category\': \'%s\', \'variant\': \'%s\', \'quantity\': %d, \'coupon\': \'%s\' }', $product->post->post_title, $product->get_sku(), $product->get_price(), $brand, $terms[0]->name, $product->get_sku(), $item['quantity'], 'CouponCode' );
			}
			$str .= implode(',', $strArr);
			$str .= ']}}}';
		endif;

		$str .= ');</script>';
		// print script
		print($str);
	} else {
		return false;
	}
}
