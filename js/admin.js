jQuery(document).on( 'click', '.wll-update-notice-newsletter .notice-dismiss', function() {

    jQuery.ajax({
        url: ajaxurl,
        data: {
            action: 'wll_hide_subscription_notice'
        }
    })

})

jQuery(document).on( 'click', '#wholesale_customers_subscribe', function( e ){

	e.preventDefault();

	var email_address = jQuery("#wll_user_subscribe_to_newsletter").val();

	var data = {
        action: 'wholesale_customers_woo_newsletter',
        email: email_address
	}
	console.log(data);
	jQuery.post( ajaxurl, data, function( response ){
		console.log(response);
		if( response ){
			jQuery("#wll_user_subscribe_to_newsletter").attr( 'disabled', 'true');
			jQuery("#wholesale_customers_subscribe").attr( 'disabled', 'true');
			jQuery(".wll-update-notice-newsletter").append("<p>You have been successfully subscribed to our newsletter and will receive your coupon code shortly. Thank you</p>");
		}

	});

});