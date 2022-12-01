/**
 * Allow the site theme or other plugins to create analytics tracking events
 *
 * @param {string} type
 * @param {string} category
 * @param {string} action
 * @param {string} label
 * @param {Array}  value
 */
function analyticsTrackingEvent(type, category, action, label) {
	if ( typeof wp !== 'undefined' ) {
		wp.hooks.doAction('minnpostFormProcessorMailchimpAnalyticsEvent', type, category, action, label);
	}
}

/**
 * Allow the plugin to send data to the dataLayer object for Google Tag Manager
 *
 * @param {string} type
 * @param {string} formId
 * @param {string} formClasses
 * @param {string} action
 */
function dataLayerEvent( type, formId, formClasses, action ) {
	if ( typeof wp !== 'undefined' ) {
		let dataLayerContent = {
			'event': 'ajaxFormSubmission',
			'type': type,
			'formId': formId,
			'formClasses': formClasses,
			'action': action
		};
		wp.hooks.doAction('minnpostFormProcessorMailchimpDataLayerEvent', dataLayerContent );
	}
}

function shortcodeForm() {

	const mailchimpForms = document.querySelectorAll( '.m-form-minnpost-form-processor-mailchimp' );
	if ( 0 < mailchimpForms.length ) {

		mailchimpForms.forEach( function ( mailchimpForm ) {
			let formId = mailchimpForm.id;
			let formClasses = mailchimpForm.className;
			let button = mailchimpForm.querySelector( 'button' );
			let messageElement = mailchimpForm.querySelector( '.m-form-message-ajax' );
			if (messageElement ) {
				button.addEventListener( 'click', function ( event ) {
					event.preventDefault(); // Prevent the default form submit.
					event.stopImmediatePropagation();
					button.disabled = true;
					let buttonText = event.target.textContent || event.target.innerText;
					button.innerHTML = 'Processing';
					let data = new FormData( mailchimpForm );
					data.set( 'ajaxrequest', 'true' );
					data.set( 'subscribe', 'true' );

					let message      = '';
					let messageClass = 'info';
					let analyticsAction = 'Signup';
					let formType = 'Newsletter';

					fetch( params.ajaxurl, {
						method: 'POST',
						body: data
					} )
					.then( response => response.json() )
					.then( data => {
						if ( true === data.success ) {
							messageClass     = 'info';
							button.innerHTML = 'Thanks';
							switch ( data.data.user_status ) {
								case 'existing':
									analyticsAction = 'Update';
									break;
								case 'new':
									analyticsAction = 'Signup';
									break;
								case 'pending':
									analyticsAction = 'Signup';
									break;
							}
							if ( '' !== data.data.confirm_message ) {
								message = data.data.confirm_message;
							}
							if ( 'function' === typeof analyticsTrackingEvent ) {
								analyticsTrackingEvent( 'event', formType, analyticsAction, location.pathname );
							}
							if ( 'function' === typeof dataLayerEvent ) {
								dataLayerEvent( formType, formId, formClasses, analyticsAction );
							}
						} else {
							messageClass     = 'error';
							button.disabled  = false;
							button.innerHTML = buttonText;
							if ( 'function' === typeof analyticsTrackingEvent ) {
								analyticsTrackingEvent( 'event', formType, 'Fail', location.pathname );
							}
							if ( 'function' === typeof dataLayerEvent ) {
								dataLayerEvent( formType, formId, formClasses, 'Fail' );
							}
							if ( '' !== data.data.confirm_message ) {
								message = data.data.confirm_message;
							}
						}
						messageElement.innerHTML = message;
						messageElement.classList.add( 'm-form-message-' + messageClass );
						messageElement.classList.remove( 'm-form-message-ajax-placeholder' );
						mailchimpForm.classList.add( 'm-form-minnpost-form-processor-mailchimp-submitted' );
						if ( mailchimpForm.classList.contains( 'm-form-fullpage' ) ) {
							messageElement.scrollIntoView({
								behavior: 'smooth'
							});								
						}
					} )
					.catch( error => {
						messageElement.innerHTML = '<p>An error has occured. Please try again.</p>';
						messageElement.classList.add( 'm-form-message-error' );
						messageElement.classList.remove( 'm-form-message-ajax-placeholder' );
						mailchimpForm.classList.remove( 'm-form-minnpost-form-processor-mailchimp-submitted' );
						button.disabled = false;
						button.innerHTML = buttonText;
						if ( 'function' === typeof analyticsTrackingEvent ) {
							analyticsTrackingEvent( 'event', 'Newsletter', 'Fail', location.pathname );
						}
						if ( 'function' === typeof dataLayerEvent ) {
							dataLayerEvent( formType, formId, formClasses, 'Fail' );
						}
					} );

				});
			}
		} );
	}
}

document.addEventListener( 'DOMContentLoaded', function() {
	shortcodeForm();
} );
