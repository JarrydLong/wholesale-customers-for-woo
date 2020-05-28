<?php
/**
 * Plugin Name: Wholesale Customers For Woo
 * Description: Allow wholesale pricing for WooCommerce.
 * Author: Yoohoo Plugins
 * Author URI: https://yoohooplugins.com
 * Plugin URI: https://yoohooplugins.com?utm_source=woo_plugin
 * Version: 1.0.5
 * License: GPL2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wholesale-customers-for-woo
 * Network: false
 * 
 * WC requires at least: 3.0
 * WC tested up to: 4.1
 */

defined( 'ABSPATH' ) or exit;

// include settings page.
include( 'wholesale-customers-settings.php' );

function wcs_apply_wholesale_pricing( $price, $product ) {
	global $current_user;

	//let's just get the price
	$discount_price = floatval( $price );

	$is_wholesale = get_user_meta( $current_user->ID, 'wcs_wholesale_customer', true );

	if( $is_wholesale != '1' ) {
		return $price;
	}
 
	// check to see if the current product has custom post meta, if it does don't apply the global discount.
	$wholesale_price = get_post_meta( $product->get_id(), 'wholesale_price', true );

	if( isset( $wholesale_price ) && $wholesale_price !== '' ) {
		return $wholesale_price;
	}

	$wcs_global_discount = floatval( get_option( 'wcs_global_discount', true ) );

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

function wcs_calculate_variation_range_prices( $variation_range_html, $product ) {
	global $current_user;

	$is_wholesale = get_user_meta( $current_user->ID, 'wcs_wholesale_customer', true );
	$prices = $product->get_variation_prices( true );

	if( !$is_wholesale ) {
		$min_price     = current( $prices['price'] );
		$max_price     = end( $prices['price'] );

		return wc_format_price_range( $min_price, $max_price );
	}

	
	// Check variation pricing for wholesale customers (individual pricing)
	$product_id = $product->get_id();
	$product_variables = new WC_Product_Variable( $product_id );
	$variables = $product_variables->get_available_variations();

	if( ! empty( $variables ) ) {

		//we may need this later.
		$wcs_global_discount = floatval( get_option( 'wcs_global_discount', true ) );

		if( $wcs_global_discount ) {
			$percentage = $wcs_global_discount / 100;
		}
	
		$prices['wholesale_price'] = array();

		foreach( $variables as $variation ) {

			$id = $variation['variation_id'];

			$wholesale_price = get_post_meta( $id, 'wholesale_price', true );

				//if individual wholesale price is set for variation
				if( isset( $wholesale_price ) && $wholesale_price !== '' ){

					$prices['wholesale_price'][$id] = $wholesale_price;

				}else{

					// check to see if global discount is set and apply discount. ABSTRACT THIS TO A FUNCTION!!!!
					if( $wcs_global_discount ) {
						$discount = $prices['price'][$id] * $percentage;
						$price_w_discount = $prices['price'][$id] - $discount;

						//add to array now.
						$prices['wholesale_price'][$id] =  sprintf( __( '%.2f', 'wholesale-customers-for-woo' ), $price_w_discount );

					}else{
						$prices['wholesale_price'][$id] = $prices['price'][$id];	
					}	
				}
		}

		if( ! empty( $prices['wholesale_price'] ) ) {

		// Sort from low to high
		asort( $prices['wholesale_price'] );

		$wholesale_min_price = current( $prices['wholesale_price'] );
		$wholesale_max_price = end( $prices['wholesale_price'] );

		}
		
	}else{

		$min_price     = current( $prices['price'] );
		$max_price     = end( $prices['price'] );

		
		$wholesale_min_price = wcs_apply_wholesale_pricing( $min_price, $product );
		$wholesale_max_price = wcs_apply_wholesale_pricing( $max_price, $product );	   

	}

	if( $wholesale_min_price == $wholesale_max_price ) {
        
        return wc_price( $wholesale_max_price );
    
    } else {
    
        return wc_format_price_range($wholesale_min_price, $wholesale_max_price);  
    
    }  
	
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

	// Get float for minimum cart amount
	$minimum = floatval( get_option( 'wcs_min_cart_amount' ) );

	if( $minimum === 0 || empty( $minimum ) ) {
		return;
	}

	if ( WC()->cart->total < $minimum ) {	

    	if( is_cart() ) {

            wc_print_notice( 
                sprintf( __( 'You must have an order with a minimum of %s to place your order, your current order total is %s.', 'wholesale-customers-for-woo' ) , 
                    wc_price( $minimum ), 
                    wc_price( WC()->cart->total )
                ), 'error' 
            );

        } else {

            wc_add_notice( 
                sprintf( __( 'You must have an order with a minimum of %s to place your order, your current order total is %s.', 'wholesale-customers-for-woo' ) , 
                    wc_price( $minimum ), 
                    wc_price( WC()->cart->total )
                ), 'error' 
            );

        }
    }

}

add_action( 'woocommerce_checkout_process', 'wcs_minimum_cart_total' );
add_action( 'woocommerce_before_cart' , 'wcs_minimum_cart_total' );


function wcs_cost_product_field() {
    woocommerce_wp_text_input( array( 'id' => 'wholesale_price', 'class' => 'wc_input_price short', 'label' => __( 'Wholesale price', 'wholesale-customers-for-woo' ) . ' (' . get_woocommerce_currency_symbol() . ')' ) );
}

add_action( 'woocommerce_product_options_pricing', 'wcs_cost_product_field' );

function wcs_cost_save_product( $product_id ) {
 
     // stop the quick edit interferring as this will stop it saving properly, when a user uses quick edit feature
    if ( isset($_POST['_inline_edit']) && wp_verify_nonce($_POST['_inline_edit'], 'inlineeditnonce' ) ) {
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

add_action( 'save_post', 'wcs_cost_save_product' );


/**
 * Add custom pricing fields to Variations.
 *
 * From http://www.remicorson.com/woocommerce-custom-fields-for-variations/
 */

function wcs_variation_settings_fields( $loop, $variation_data, $variation ) {
	
	woocommerce_wp_text_input( 
		array( 
			'id'          => 'wholesale_price[' . $variation->ID . ']', 
			'label'       => __( 'Wholesale price (' . get_woocommerce_currency_symbol() . ')', 'wholesale-customers-for-woo' ), 
			'desc_tip'    => 'true',
			'description' => __( 'This price will be available to wholesale customers only. Overrites global discount.', 'wholesale-customers-for-woo' ),
			'value'       => get_post_meta( $variation->ID, 'wholesale_price', true ),
			'data_type'		  => 'price',
		)
	);

}

add_action( 'woocommerce_variation_options_pricing', 'wcs_variation_settings_fields', 5, 3 );

// save custom fields for variations.
function wcs_save_variation_settings_fields( $post_id ) { 

	$number_field = $_POST['wholesale_price'][ $post_id ];

	if( isset( $number_field ) ) {

		if( intval($number_field) < 0 ){
			$number_field = 0;
		}
		update_post_meta( $post_id, 'wholesale_price', esc_attr( $number_field ) );
	}else{
		delete_post_meta( $post_id, 'wholesale_price' );
	}

}
add_action( 'woocommerce_save_product_variation', 'wcs_save_variation_settings_fields', 10, 1 );