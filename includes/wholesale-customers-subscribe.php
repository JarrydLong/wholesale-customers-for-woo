<?php

function wholesale_customers_woo_newsletter_callback(){

	if( isset( $_POST['action'] ) && $_POST['action'] == 'wholesale_customers_woo_newsletter' ){

		if( isset( $_POST['email'] ) && $_POST['email'] != "" ){

			$data = array( 
				'action' 	=> 'wholesale_customers_woo_newsletter', 
				'email' 	=> $_POST['email'],
				'type' 		=> 'plugin'
			);

			$request = wp_remote_post( 'https://pacificplugins.com/api/subscribe.php', array( 'body' => $data ) );

			if( !is_wp_error( $request ) ){

				$request_body = wp_remote_retrieve_body( $request );

				$response = json_decode( $request_body );

				if( !empty( $response->status ) && $response->status == 'subscribed' ){
				  	
				  	$user = wp_get_current_user();

					update_user_meta( $user->ID, 'wholesale_customers_newsletter_popup', 1 );

					echo 1;

				}

			}

		} else {

		  _e('Please enter in an email address to subscribe to our mailing list and receive your 20% coupon', 'wholesale-customers-for-woo');

		}

	}

	wp_die();

}
add_action( 'wp_ajax_wholesale_customers_woo_newsletter', 'wholesale_customers_woo_newsletter_callback' );

function wholesale_customers_woo_admin_notices(){

	$user = wp_get_current_user();

	if( get_user_meta( $user->ID, 'wholesale_customers_newsletter_popup', true ) !== '1'){
    	?>
	        <div class="notice notice-success  pps-update-notice-newsletter is-dismissible" >
		        <h3><?php _e('Wholesale Customers for Woocommerce', 'wholesale-customers-for-woo'); ?></h3>
		        <p><?php printf( __( 'Thank you for using Wholesale Customers for Woo. If you find this plugin useful please consider leaving a 5 star review %s.', 'wholesale-customers-for-woo' ), '<a href="https://wordpress.org/plugins/wholesale-customers-for-woo/#reviews" target="_blank">here</a>' ); ?></p>

		        <p><?php printf( __( 'Sign up for our newsletter to get the latest product news and promotions, plus get 20&percnt; off the %s.', 'wholesale-customers-for-woo' ), '<a href="https://pacificplugins.com/downloads/wholesale-customers-for-woocommerce-pro/">Wholesale Customers for Woocommerce <strong>'.__('Pro Version', 'wholesale-customers-for-woo').'</strong></a>' ); ?></p>

		        <p><input type='email' style='width: 250px;' name='pps_user_subscribe_to_newsletter' id='pps_user_subscribe_to_newsletter' value='<?php echo $user->data->user_email; ?>' /><button class='button button-primary' id='wholesale_customers_subscribe'><?php _e('Subscribe Me!', 'wholesale-customers-for-woo'); ?></button></p>
	        </div>
        <?php
	}

}
add_action( 'admin_notices', 'wholesale_customers_woo_admin_notices' );