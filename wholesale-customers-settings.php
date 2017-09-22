<?php

/**
 * Settings page and user meta options for Wholesale Customers
 */

function wcs_add_wholesale_option_to_users( $user ) { 

	$wcs_wholesale_customer = get_user_meta( $user->ID, 'wcs_wholesale_customer', true );
?>

	<h3><?php _e( 'Wholesale Customer User Settings', 'wholesale-customer' ); ?></h3>

	<table class="form-table">

		<tr>
			<th><label for="wholesale-customer"><?php _e( 'Wholesale customer', 'wholesale-customer' ); ?></label></th>

			<td>
				<input type="checkbox" name="wcs_wholesale_customer" id="wcs_wholesale_customer" value="1" <?php checked( $wcs_wholesale_customer, 1 );?>/><?php _e( 'Check this option to set this user to receive your wholesale pricing', 'wholesale-customer' ); ?><br />
				<!-- <span class="description"></span> -->
			</td>
		</tr>

	</table>
<?php }

add_action( 'show_user_profile', 'wcs_add_wholesale_option_to_users' );
add_action( 'edit_user_profile', 'wcs_add_wholesale_option_to_users' );


function wcs_save_wholesale_option_to_users( $user_id ) {

	if ( !current_user_can( 'edit_user', $user_id ) ) {
		return;
	}

	if( isset( $_POST['wcs_wholesale_customer']) ){
		$wcs_wholesale_customer = 1;
	}else{
		$wcs_wholesale_customer = 0;
	}

	update_user_meta( $user_id, 'wcs_wholesale_customer', $wcs_wholesale_customer );
}
add_action( 'personal_options_update', 'wcs_save_wholesale_option_to_users' );
add_action( 'edit_user_profile_update', 'wcs_save_wholesale_option_to_users' );

/**
 * Add option to WooCommerce > Settings > Pricing Options.
 */
function wcs_global_discount_settings( $settings ) {

  $updated_settings = array();

  foreach ( $settings as $section ) {

    // at the bottom of the Pricing Options section
    if ( isset( $section['id'] ) && 'pricing_options' == $section['id'] &&

       isset( $section['type'] ) && 'sectionend' == $section['type'] ) {

      $updated_settings[] = array(

        'name'     => __( 'Wholesale Global Discount', 'wholesale-customer' ),
        'desc_tip' => __( 'This will give a global discount to all products on your WooCommerce Store.', 'wholesale-customer' ),
        'id'       => 'wcs_global_discount',
        'type'     => 'number',
        'css'      => 'min-width:300px;',
        'std'      => '0',  // WC < 2.0
        'default'  => '0',  // WC >= 2.0
        'custom_attributes' => array(
			'min'  => 0,
			'max'  => 100,
			'step' => 1,
		),
        'desc'     => __( 'Enter a percentage value (without % symbol).', 'wholesale-customer' ),

      );

    }

    $updated_settings[] = $section;

  }

  return $updated_settings;

}
add_filter( 'woocommerce_general_settings', 'wcs_global_discount_settings' );