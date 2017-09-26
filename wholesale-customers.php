<?php
/**
 * Plugin Name: Wholesale Customers For Woo
 * Description: Allow wholesale pricing for WooCommerce.
 * Author: YooHoo Plugins
 * Author URI: https://yoohooplugins.com
 * Version: 1.0
 * License: GPL2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wholesale-customers
 * Network: false
 */

defined( 'ABSPATH' ) or exit;

// include settings page.
include( 'wholesale-customers-settings.php' );

function wcs_apply_wholesale_pricing( $price, $product ) {
	global $current_user;

	//let's just get the price
	$discount_price = (int) $price;

	$is_wholesale = get_user_meta( $current_user->ID, 'wcs_wholesale_customer', true );

	if( $is_wholesale != '1' ) {
		return $discount_price;
	}

	// check to see if the current product has custom post meta, if it does don't apply the global discount.
	$wholesale_price = get_post_meta( $product->get_id(), 'wholesale_price', true );

	if( $wholesale_price ) {
		return $wholesale_price;
	}

	$wcs_global_discount = (int) get_option( 'wcs_global_discount', true );

	if( empty( $wcs_global_discount ) && $wcs_global_discount < 1 ){
		return $discount_price;
	}

	$percentage = $wcs_global_discount / 100;

	$discount_amount = $discount_price * $percentage;

	$discount_price = $discount_price - $discount_amount;

	//if the discounted price is below $0 set it to $0.
		if( $discount_price <= 0 ){
			$discount_price = 0;
		}


	
	return $discount_price;
}

if ( !is_admin() || defined('DOING_AJAX') ) {    
	add_filter("woocommerce_product_get_price", "wcs_apply_wholesale_pricing", 10, 2);
	add_filter("woocommerce_product_variation_get_price", "wcs_apply_wholesale_pricing", 10, 2 );
	add_filter("woocommerce_variable_price_html", "wcs_calculate_variation_range_prices", 10, 2);
}

function wcs_calculate_variation_range_prices($variation_range_html, $product) {
	$prices = $product->get_variation_prices( true );
	$min_price     = current($prices['price']);
	$max_price     = end($prices['price']);
	
	$wholesale_min_price = wcs_apply_wholesale_pricing($min_price, $product);
	$wholesale_max_price = wcs_apply_wholesale_pricing($max_price, $product);	   
	
	return wc_format_price_range($wholesale_min_price, $wholesale_max_price);
}

/**
 * Code for Minimum Cart Total.
 * Taken from https://docs.woocommerce.com/document/minimum-order-amount/
 * @since 1.0.1
 */
function wcs_minimum_cart_total(){
	global $current_user;

	$wholesale_customer = get_user_meta( $current_user->ID, 'wcs_wholesale_customer', true );

	// Bail if customer isn't wholesale customer
	if( ! $wholesale_customer ){
		return;
	}

	$minimum = (int) get_option( 'wcs_min_cart_amount' );

	if( $minimum === 0 || empty( $minimum ) ) {
		return;
	}

	if ( WC()->cart->total < $minimum ) {	

    	if( is_cart() ) {

            wc_print_notice( 
                sprintf( 'You must have an order with a minimum of %s to place your order, your current order total is %s.' , 
                    wc_price( $minimum ), 
                    wc_price( WC()->cart->total )
                ), 'error' 
            );

        } else {

            wc_add_notice( 
                sprintf( 'You must have an order with a minimum of %s to place your order, your current order total is %s.' , 
                    wc_price( $minimum ), 
                    wc_price( WC()->cart->total )
                ), 'error' 
            );

        }
    }

}

add_action( 'woocommerce_checkout_process', 'wcs_minimum_cart_total' );
add_action( 'woocommerce_before_cart' , 'wcs_minimum_cart_total' );


function wc_cost_product_field() {
    woocommerce_wp_text_input( array( 'id' => 'wholesale_price', 'class' => 'wc_input_price short', 'label' => __( 'Wholesale Price', 'wholesale-customers' ) . ' (' . get_woocommerce_currency_symbol() . ')' ) );
}

add_action( 'woocommerce_product_options_pricing', 'wc_cost_product_field' );

function wc_cost_save_product( $product_id ) {
 
     // stop the quick edit interferring as this will stop it saving properly, when a user uses quick edit feature
    if ( wp_verify_nonce($_POST['_inline_edit'], 'inlineeditnonce' ) ) {
    	return;
    }

 
    // If this is a auto save do nothing, we only save when update button is clicked
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}


	if ( isset( $_POST['wholesale_price'] ) && !empty( $_POST['wholesale_price'])) {
		update_post_meta( $product_id, 'wholesale_price', $_POST['wholesale_price'] );
	} else {
		delete_post_meta( $product_id, 'wholesale_price' );
	}
}

add_action( 'save_post', 'wc_cost_save_product' );