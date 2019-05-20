( function( $ ) {

	function gtag_report_conversion( url ) {
		var callback = function () {
		  if ( 'undefined' !== typeof( url ) ) {
		    window.location = url;
		  }
		};
		gtag( 'event', 'conversion', {
		  'send_to': params.gtag_sendto,
		  'event_callback': callback
		} );
		return false;
	}

	function shortcodeForm() {
		if ( $( '.m-form-minnpost-form-processor-mailchimp' ).length > 0 ) {
			$( '.m-form-minnpost-form-processor-mailchimp' ).submit( function( event ) {
				event.preventDefault(); // Prevent the default form submit.
				event.stopImmediatePropagation();
				var that = this;
				var button = $( 'button', this );
				var previous_button_text = button.text();
				var ajax_form_data = $( this ).serialize(); // serialize the form data
				button.prop( 'disabled', true );
				button.text( 'Processing' );
				//add our own ajax check as X-Requested-With is not always reliable
				ajax_form_data = ajax_form_data + '&ajaxrequest=true&subscribe';
				$.ajax({
					cache: false,
					url: params.ajaxurl, // domain/wp-admin/admin-ajax.php
					type: 'post',
					dataType : 'json',
					data: ajax_form_data
				} )
				.done( function( response ) { // response from the PHP action
					var message       = '';
					var message_class = 'info';
					if ( true === response.success ) {
						button.text( 'Thanks' );
						var analytics_action = 'Signup';
						switch ( response.data.user_status ) {
							case 'existing':
								analytics_action = 'Update';
								message = 'Thanks for updating your email preferences. They will go into effect immediately.';
								break;
							case 'new':
								analytics_action = 'Signup';
								message = 'We have added you to the MinnPost mailing list.';
								break;
							case 'pending':
								analytics_action = 'Signup';
								message = 'We have added you to the MinnPost mailing list. You will need to click the confirmation link in the email we sent to begin receiving messages.';
								break;
						}
						if ( '' !== response.data.confirm_message ) {
							message = response.data.confirm_message;
						}

						if ( 'function' === typeof wp_analytics_tracking_event ) {
							wp_analytics_tracking_event( 'event', 'Newsletter', analytics_action, location.pathname );
							gtag_report_conversion( location.pathname );
						}
					} else {
						button.prop( 'disabled', false );
						button.text( previous_button_text );
						if ( 'function' === typeof wp_analytics_tracking_event ) {
							wp_analytics_tracking_event( 'event', 'Newsletter', 'Fail', location.pathname );
						}
						if ( '' !== response.data.confirm_message ) {
							message = response.data.confirm_message;
						}
						message_class = 'error';
					}
					$( '.m-form-message-ajax' ).html( message );
					$( '.m-form-message-ajax' ).addClass( 'm-form-message-' + message_class ).removeClass( 'm-form-message-ajax-placeholder' );
				} )
				.fail( function( response ) {
					$( '.m-form-message-ajax' ).html( '<p>An error has occured. Please try again.</p>' );
					$( '.m-form-message-ajax' ).addClass( 'm-form-message-error' ).removeClass( 'm-form-message-ajax-placeholder' );
					button.prop( 'disabled', false );
					button.text( previous_button_text );
					if ( 'function' === typeof wp_analytics_tracking_event ) {
						wp_analytics_tracking_event( 'event', 'Newsletter', 'Fail', location.pathname );
					}
				} );
			});
		}
	}

	jQuery( document ).ready( function( $ ) {
		shortcodeForm();
	});

})(jQuery);