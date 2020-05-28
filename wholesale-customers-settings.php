<?php

// New Class Fam

class WCS_Settings {

    public static function hooks() {

    	add_action( 'show_user_profile', array( 'WCS_Settings', 'add_wholesale_option_to_users' ) );
		add_action( 'edit_user_profile', array( 'WCS_Settings', 'add_wholesale_option_to_users' ) );

		add_action( 'personal_options_update', array( 'WCS_Settings', 'save_wholesale_option_to_users' ) );
		add_action( 'edit_user_profile_update', array( 'WCS_Settings', 'save_wholesale_option_to_users' ) );

		add_action( 'admin_menu', array( 'WCS_Settings', 'add_submenu_page' ), 99 );

    	add_filter( 'woocommerce_settings_tabs_array', array( 'WCS_Settings', 'add_settings_tab'), 50 );
        add_action( 'woocommerce_settings_tabs_wcs_settings', array( 'WCS_Settings', 'settings_tab' ) );
        add_action( 'woocommerce_update_options_wcs_settings', array( 'WCS_Settings', 'update_settings' ) );

        add_filter( 'manage_edit-product_columns', array( 'WCS_Settings','add_custom_wholesale_cost_column_header' ) );

        add_action( 'manage_product_posts_custom_column', array( 'WCS_Settings','add_custom_wholesale_cost_column' ), 10, 2 );

        add_action( 'admin_head', array( 'WCS_Settings', 'hide_column_small_devices' ) );

    }

    public static function add_submenu_page() {
    	add_submenu_page( 'woocommerce', 'Wholesale Settings', 'Wholesale Settings', 'manage_woocommerce', 'admin.php?page=wc-settings&tab=wcs_settings' ); 
	}


    public static function add_wholesale_option_to_users( $user ) { 

	$wcs_wholesale_customer = get_user_meta( $user->ID, 'wcs_wholesale_customer', true );
	?>

	<h3><?php _e( 'Wholesale Customer User Settings', 'wholesale-customers-for-woo' ); ?></h3>

	<table class="form-table">

		<tr>
			<th><label for="wholesale-customer"><?php _e( 'Wholesale customer', 'wholesale-customers-for-woo' ); ?></label></th>

			<td>
				<input type="checkbox" name="wcs_wholesale_customer" id="wcs_wholesale_customer" value="1" <?php checked( $wcs_wholesale_customer, 1 );?>/><?php _e( 'Check this option to set this user to receive your wholesale pricing', 'wholesale-customers-for-woo' ); ?><br />
				<!-- <span class="description"></span> -->
			</td>
		</tr>

	</table>
	<?php }


	public static function save_wholesale_option_to_users( $user_id ) {

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


    /**
     * Add a new settings tab to the WooCommerce settings tabs array.
     *
     * @param array $settings_tabs Array of WooCommerce setting tabs & their labels, excluding the Subscription tab.
     * @return array $settings_tabs Array of WooCommerce setting tabs & their labels, including the Subscription tab.
     */
    public static function add_settings_tab( $settings_tabs ) {
        $settings_tabs['wcs_settings'] = __( 'Wholesale Settings', 'wholesale-customers-for-woo' );
        return $settings_tabs;
    }


    /**
     * Uses the WooCommerce admin fields API to output settings via the @see woocommerce_admin_fields() function.
     *
     * @uses woocommerce_admin_fields()
     * @uses self::get_settings()
     */
    public static function settings_tab() {
        woocommerce_admin_fields( self::get_settings() );
    }


    /**
     * Uses the WooCommerce options API to save settings via the @see woocommerce_update_options() function.
     *
     * @uses woocommerce_update_options()
     * @uses self::get_settings()
     */
    public static function update_settings() {
        woocommerce_update_options( self::get_settings() );
    }


    /**
     * Get all the settings for this plugin for @see woocommerce_admin_fields() function.
     *
     * @return array Array of settings for @see woocommerce_admin_fields() function.
     */
    public static function get_settings() {

        $settings = array(
            'section_title' => array(
                'name'     => __( 'Wholesale Settings', 'wholesale-customers-for-woo' ),
                'type'     => 'title',
                'desc'     => '',
                'id'       => 'wcs_settings_tab_section_title'
            ),

    		'global_discount' => array(
    		    'name'     => __( 'Wholesale Global Discount', 'wholesale-customers-for-woo' ),
        		'desc_tip' => __( 'This will give a global discount to all products on your WooCommerce Store.', 'wholesale-customers-for-woo' ),
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
        		'desc'     => __( 'Enter a percentage value (without % symbol).', 'wholesale-customers-for-woo' ),
        	),

        	'min_cart_amount' => array(
        		'name'     => __( 'Minimum Cart Total', 'wholesale-customers-for-woo' ),
        		'desc_tip' => __( 'Force wholesale customers to spend a minimum before allowing checkout.', 'wholesale-customers-for-woo' ),
		        'id'       => 'wcs_min_cart_amount',
		        'type'     => 'number',
		        'css'      => 'min-width:300px;',
		        'std'      => '0',  // WC < 2.0
		        'default'  => '0',  // WC >= 2.0
		        'custom_attributes' => array(
					'min'  => 0,
					'step' => 1,
				),
        		'desc'     => __( 'Leave blank to ignore this feature.', 'wholesale-customers-for-woo' ),

        	),

            'section_end' => array(
                 'type' => 'sectionend',
                 'id' => 'wcs_settings_tab_section_end'
            )
        );

        return apply_filters( 'wcs_settings_tab_settings', $settings );
    }

    public static function add_custom_wholesale_cost_column_header( $columns ){

        $columns['wholesale_price'] = __( 'Wholesale price', 'wholesale-customers-for-woo' );

        return $columns;

    }

    public static function add_custom_wholesale_cost_column( $column, $post_id ) {

        $wholesale_price = get_post_meta( $post_id, 'wholesale_price', true );
        $currency = get_woocommerce_currency_symbol();


        // Check if variable.
        $product_variables = new WC_Product_Variable( $post_id);
        $variables = $product_variables->get_available_variations();

        // if( ! empty( $variables ) ) {

        //   $wholesale_variation = array();

        //   foreach( $variables as $variation ){

        //     $wholesale_variation[] = $variation['display_price'];

        //   }

        // asort( $wholesale_variation );

        // $min_price = current( $wholesale_variation );
        // $max_price = end( $wholesale_variation ); 
        
        // }  
       
        if ( $column == 'wholesale_price' ) {

            if( ! empty( $variables ) ){
                //echo $currency . number_format( $min_price, 2 ) . ' - ' . $currency . number_format( $max_price, 2 );
                _e( 'N/A', 'wholesale-customers-for-woo' );
            }elseif( $wholesale_price ) {
                 echo $currency . number_format( $wholesale_price, 2 );
            }else{
                _e( 'N/A', 'wholesale-customers-for-woo' );
            }

        }
    }

    public static function hide_column_small_devices() {
        if ( isset( $_REQUEST['post_type']) && $_REQUEST['post_type'] === 'product' ) {
            ?>
            <style>
            @media only screen and (max-width: 1300px) {
                th.manage-column.column-wholesale_price, td.wholesale_price.column-wholesale_price { display: none; }
            }
            </style>
            <?php
        }
    }

} // end class

WCS_Settings::hooks();


