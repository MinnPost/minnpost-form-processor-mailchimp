( function( $ ) {

	/**
	 * Allow the site theme or other plugins to create analytics tracking events
	 *
	 * @param {string} type
	 * @param {string} category
	 * @param {string} action
	 * @param {string} label
	 * @param {Array}  value
	 */
	function analyticsTrackingEvent(type, category, action, label, value) {
		if ( typeof wp !== 'undefined' ) {
			wp.hooks.doAction('minnpostFormProcessorMailchimpAnalyticsEvent', type, category, action, label, value);
		}
	}

	function shortcodeForm() {
		if ( $( '.m-form-minnpost-form-processor-mailchimp' ).length > 0 ) {
			$( '.m-form-minnpost-form-processor-mailchimp' ).submit( function( event ) {
				event.preventDefault(); // Prevent the default form submit.
				event.stopImmediatePropagation();
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
								break;
							case 'new':
								analytics_action = 'Signup';
								break;
							case 'pending':
								analytics_action = 'Signup';
								break;
						}
						if ( '' !== response.data.confirm_message ) {
							message = response.data.confirm_message;
						}

						if ( 'function' === typeof analyticsTrackingEvent ) {
							analyticsTrackingEvent( 'event', 'Newsletter', analytics_action, location.pathname );
						}
					} else {
						button.prop( 'disabled', false );
						button.text( previous_button_text );
						if ( 'function' === typeof analyticsTrackingEvent ) {
							analyticsTrackingEvent( 'event', 'Newsletter', 'Fail', location.pathname );
						}
						if ( '' !== response.data.confirm_message ) {
							message = response.data.confirm_message;
						}
						message_class = 'error';
					}
					$( '.m-form-message-ajax' ).html( message );
					$( '.m-form-message-ajax' ).addClass( 'm-form-message-' + message_class ).removeClass( 'm-form-message-ajax-placeholder' );
					$( '.m-form-minnpost-form-processor-mailchimp' ).addClass( 'm-form-minnpost-form-processor-mailchimp-submitted' );
					if ( $( '.m-form-fullpage' ).length > 0 ) {
						// if we are on a fullpage, scroll to the result message
						$( 'html, body' ).animate( {
							scrollTop: $( '.m-form-message-ajax' ).offset().top - 50
						}, 'slow' );
					}
				} )
				.fail( function( response ) {
					$( '.m-form-message-ajax' ).html( '<p>An error has occured. Please try again.</p>' );
					$( '.m-form-message-ajax' ).addClass( 'm-form-message-error' ).removeClass( 'm-form-message-ajax-placeholder' );
					$( '.m-form-minnpost-form-processor-mailchimp' ).removeClass( 'm-form-minnpost-form-processor-mailchimp-submitted' );
					button.prop( 'disabled', false );
					button.text( previous_button_text );
					if ( 'function' === typeof analyticsTrackingEvent ) {
						analyticsTrackingEvent( 'event', 'Newsletter', 'Fail', location.pathname );
					}
				} );
			});
		}
	}

	jQuery( document ).ready( function( $ ) {
		shortcodeForm();
	});

})(jQuery);