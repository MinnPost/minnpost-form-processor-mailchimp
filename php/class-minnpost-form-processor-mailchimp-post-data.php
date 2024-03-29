<?php
/**
 * Class file for the MinnPost_Form_Processor_MailChimp_Post_Data class.
 *
 * @file
 */

if ( ! class_exists( 'MinnPost_Form_Processor_MailChimp' ) ) {
	die();
}

/**
 * Create form processing methods.
 */
class MinnPost_Form_Processor_MailChimp_Post_Data {

	public $option_prefix;
	public $parent_option_prefix;
	public $version;
	public $slug;
	public $get_data;
	public $parent;

	/**
	* Constructor which sets up post data processing
	*/
	public function __construct() {
		$this->option_prefix        = minnpost_form_processor_mailchimp()->option_prefix;
		$this->parent_option_prefix = minnpost_form_processor_mailchimp()->parent_option_prefix;
		$this->version              = minnpost_form_processor_mailchimp()->version;
		$this->slug                 = minnpost_form_processor_mailchimp()->slug;
		$this->get_data             = minnpost_form_processor_mailchimp()->get_data;
		$this->parent               = minnpost_form_processor_mailchimp()->parent;

		$this->add_actions();
	}

	/**
	* Create the action hooks to handle post data
	*
	*/
	private function add_actions() {
		// all of these hooks use the same method
		// basically they allow it to work whether the user is on the front end or on the admin
		// and also whether the form is submitted with a page refresh or via ajax
		add_action( 'admin_post_nopriv_newsletter_form', array( $this, 'process_form_data' ) );
		add_action( 'admin_post_newsletter_form', array( $this, 'process_form_data' ) );
		add_action( 'wp_ajax_nopriv_newsletter_form', array( $this, 'process_form_data' ) );
		add_action( 'wp_ajax_newsletter_form', array( $this, 'process_form_data' ) );
		add_filter( 'query_vars', array( $this, 'add_query_vars' ) );
	}

	/**
	* Process a user's submitted form data
	*
	*/
	public function process_form_data() {
		$action = isset( $_POST['action'] ) ? esc_attr( $_POST['action'] ) : '';
		if ( isset( $_POST['minnpost_form_processor_mailchimp_nonce'] ) && wp_verify_nonce( $_POST['minnpost_form_processor_mailchimp_nonce'], 'minnpost_form_processor_mailchimp_nonce' ) ) {

			// todo: error handling for this?
			$resource_type    = $this->get_data->get_resource_type( $action );
			$resource_id      = $this->get_data->get_resource_id( $action );
			$subresource_type = $this->get_data->get_subresource_type( $action );

			// placement of this form.
			$placement = isset( $_POST['placement'] ) ? esc_attr( $_POST['placement'] ) : '';

			// form data about the user.
			$mailchimp_user_id = isset( $_POST['mailchimp_user_id'] ) ? esc_attr( $_POST['mailchimp_user_id'] ) : '';
			$status            = isset( $_POST['mailchimp_status'] ) ? esc_attr( $_POST['mailchimp_status'] ) : get_option( $this->option_prefix . $action . '_default_user_status', '' );
			$email             = isset( $_POST['email'] ) ? sanitize_email( $_POST['email'] ) : '';

			// if the user's form data is not present, try to get it now.
			if ( '' === $mailchimp_user_id || '' === $status ) {
				$user_info = $this->get_data->get_user_info( $action, $resource_type, $resource_id, $email, true );
				if ( ! is_wp_error( $user_info ) ) {
					$mailchimp_user_id = $user_info['id'];
					$status            = $user_info['status'];
				}
			}

			// this is the mailchimp group settings field. it gets sanitized later.
			$groups_available = isset( $_POST['groups_available'] ) ? $_POST['groups_available'] : 'default';

			// this checks for allowed groups based on the settings
			$groups_available = $this->get_data->get_shortcode_groups( $action, $resource_type, $resource_id, $groups_available );

			// this is the array of groups submitted by the user, if applicable
			$groups_submitted = isset( $_POST['groups_submitted'] ) ? (array) array_map( 'esc_attr', $_POST['groups_submitted'] ) : array();

			// if the user submitted groups, assign them the ones that are available to this form.
			// note: submitted needs to be an array of keys. whatever else available contains (ids, default, etc.) it needs to also have the id as the a column of the array.
			if ( ! empty( $groups_submitted ) ) {
				$groups = array_intersect( $groups_submitted, array_column( $groups_available, 'id' ) );
			} else {
				// otherwise, assign them whatever is available based on settings. we only need the ids.
				$groups = array_column( $groups_available, 'id' );
			}

			// optional form data
			$first_name      = isset( $_POST['first_name'] ) ? sanitize_text_field( $_POST['first_name'] ) : '';
			$last_name       = isset( $_POST['last_name'] ) ? sanitize_text_field( $_POST['last_name'] ) : '';
			$confirm_message = isset( $_POST['confirm_message'] ) ? wp_kses_post( wpautop( $_POST['confirm_message'] ) ) : '';

			// setup the mailchimp user array and add the required items to it
			$user_data = array(
				'mailchimp_user_id' => $mailchimp_user_id,
				'user_email'        => $email,
				'user_status'       => $status,
			);

			// show all the available groups so we can set them as false, if need be
			if ( ! empty( $groups_available ) ) {
				$user_data['groups_available'] = array_keys( $groups_available );
			}

			// set default mailchimp group settings based on the shortcode attributes and the plugin settings
			if ( ! empty( $groups ) ) {
				$user_data['groups'] = $groups;
			}

			// name fields are optional, but we can use them if they exist
			if ( ! empty( $first_name ) ) {
				$user_data['first_name'] = $first_name;
			}
			if ( ! empty( $last_name ) ) {
				$user_data['last_name'] = $last_name;
			}

			// save to mailchimp
			$result = $this->save_to_mailchimp( $action, $resource_type, $resource_id, $subresource_type, $user_data );

			// the confirm message might come from the api. If it does, use that.
			if ( isset( $result['confirm_message'] ) && '' !== $result['confirm_message'] ) {
				$confirm_message = $result['confirm_message'];
			}

			if ( isset( $result['id'] ) ) {
				if ( 'PUT' === $result['method'] ) {
					$user_status = 'existing';
				} elseif ( 'POST' === $result['method'] ) {
					$user_status = 'new';
					if ( 'pending' === $result['status'] ) {
						$user_status = 'pending';
					}
				}

				// clear the cache now
				//$cache_call   = $resource_type . '/' . $resource_id . '/' . $subresource_type . '/' . $email;
				//$delete_cache = $this->parent->wordpress->cache_delete( $call );

				// reset the user info. it would be nice to just clear the cache instead but that does not appear to work.
				$user_info = $this->get_data->get_user_info( $action, $resource_type, $resource_id, $email, true );

				if ( isset( $_POST['ajaxrequest'] ) && 'true' === $_POST['ajaxrequest'] ) {
					wp_send_json_success(
						array(
							'id'              => isset( $result['id'] ) ? $result['id'] : '',
							'user_status'     => $user_status,
							'confirm_message' => $this->get_data->get_success_message( 'success-' . $user_status, $confirm_message, true ),
						)
					);
				} else {
					if ( isset( $_GET['redirect_url'] ) && '' !== $_GET['redirect_url'] ) {
						$redirect_url = wp_validate_redirect( $_GET['redirect_url'] );
					} elseif ( isset( $_POST['redirect_url'] ) && '' !== $_POST['redirect_url'] ) {
						$redirect_url = wp_validate_redirect( $_POST['redirect_url'] );
					} else {
						$redirect_url = site_url();
					}
					$redirect_url = add_query_arg( 'newsletter_message_code', 'success-' . $user_status, $redirect_url );
					$redirect_url = add_query_arg( 'email', rawurlencode( $email ), $redirect_url );

					wp_redirect( $redirect_url );
					exit;
				}
			} else {
				// error handling
				$user_status = 'error';
				if ( isset( $result['status'] ) && in_array( $result['status'], array( 400, 408, 'spam' ), true ) ) {
					$confirm_message = $result['detail'];
				}
				if ( ! isset( $result['status'] ) ) {
					$result['local'] = true;
					$confirm_message = __( 'Our newsletter system was unable to complete your request, and it did not send us an error message. You may be able to try again.', 'minnpost-form-processor-mailchimp' );
				}
				if ( isset( $_POST['ajaxrequest'] ) && 'true' === $_POST['ajaxrequest'] ) {
					$local_error = false;
					if ( isset( $result['local'] ) ) {
						$local_error = ( true === $result['local'] ) ? true : false;
					}
					wp_send_json_error(
						array(
							'id'              => isset( $result['id'] ) ? $result['id'] : '',
							'user_status'     => $user_status,
							'confirm_message' => $this->get_data->get_error_message( $confirm_message, '', true, $local_error ),
						)
					);
				} else {
					if ( isset( $_GET['redirect_url'] ) && '' !== $_GET['redirect_url'] ) {
						$redirect_url = wp_validate_redirect( $_GET['redirect_url'] );
					} elseif ( isset( $_POST['redirect_url'] ) && '' !== $_POST['redirect_url'] ) {
						$redirect_url = wp_validate_redirect( $_POST['redirect_url'] );
					} else {
						$redirect_url = site_url();
					}
					$redirect_url = add_query_arg( 'newsletter_error', rawurlencode( $confirm_message ), $redirect_url );
					$redirect_url = add_query_arg( 'email', rawurlencode( $email ), $redirect_url );

					wp_redirect( $redirect_url );
					exit;
				}
			}
		} else {
			if ( isset( $_GET['redirect_url'] ) && '' !== $_GET['redirect_url'] ) {
				$redirect_url = wp_validate_redirect( $_GET['redirect_url'] );
			} elseif ( isset( $_POST['redirect_url'] ) && '' !== $_POST['redirect_url'] ) {
				$redirect_url = wp_validate_redirect( $_POST['redirect_url'] );
			} else {
				$redirect_url = site_url();
			}
			if ( isset( $_POST['ajaxrequest'] ) && 'true' === $_POST['ajaxrequest'] ) {
				wp_send_json_error();
			} else {
				wp_die(
					__( 'Invalid nonce specified', 'minnpost-form-processor-mailchimp' ),
					__( 'Error', 'minnpost-largo' ),
					array(
						'response'  => 403,
						'back_link' => $redirect_url,
					)
				);
			}
		}
	}

	/**
	 * Setup query var for message code after response from MailChimp
	 *
	 * @param  array   $query_vars
	 * @return  array   $query_vars
	 */
	public function add_query_vars( $query_vars ) {
		$query_vars[] = 'newsletter_message_code';
		$query_vars[] = 'newsletter_error';
		$query_vars[] = 'email';
		return $query_vars;
	}

	/**
	 * Send data to MailChimp. This is public because the REST API also uses it.
	 *
	 * @param  string  $shortcode
	 * @param  string  $resource_type
	 * @param  string  $resource_id
	 * @param  string  $subresource_type
	 * @param  array   $user_data
	 *
	 * @return  array   $user_data
	 */
	public function save_to_mailchimp( $shortcode, $resource_type, $resource_id, $subresource_type, $user_data ) {
		// send user data to mailchimp and create/update their info
		$id               = isset( $user_data['mailchimp_user_id'] ) ? $user_data['mailchimp_user_id'] : '';
		$status           = isset( $user_data['user_status'] ) ? $user_data['user_status'] : get_option( $this->option_prefix . $shortcode . '_default_user_status', '' );
		$email            = isset( $user_data['user_email'] ) ? $user_data['user_email'] : '';
		$first_name       = isset( $user_data['first_name'] ) ? $user_data['first_name'] : '';
		$last_name        = isset( $user_data['last_name'] ) ? $user_data['last_name'] : '';
		$groups           = isset( $user_data['groups'] ) ? $user_data['groups'] : array();
		$groups_available = isset( $user_data['groups_available'] ) ? $user_data['groups_available'] : array();

		// confirm message might come from the API.
		$confirm_message = isset( $user_data['confirm_message'] ) ? $user_data['confirm_message'] : '';

		// don't send any data to mailchimp if there are no settings, and there is no user id
		// otherwise we need to, in case user wants to empty their preferences
		if ( empty( $groups ) && '' === $id && empty( $groups_available ) ) {
			return;
		}

		$params['email_address'] = $email;
		$params['status']        = $status;
		$params['merge_fields']  = array(
			'FNAME' => $first_name,
			'LNAME' => $last_name,
		);

		$group_key = get_option( $this->option_prefix . $shortcode . '_mc_resource_item_type', '' );

		// default is false if a group is not allowed in the submitted form
		// that is the only way we can remove a subscription option if a user chooses to uncheck it
		foreach ( $groups_available as $group ) {
			if ( ! isset( $user_data['groups'] ) || ( isset( $user_data['groups'] ) && ! in_array( $group, $user_data['groups'], true ) ) ) {
				$params[ $group_key ][ $group ] = 'false';
			}
		}

		// add the groups the user actually wants
		if ( ! empty( $groups ) ) {
			foreach ( $groups as $key => $value ) {
				$params[ $group_key ][ $value ] = 'true';
			}
		}

		if ( '' !== $id ) {
			$http_method = 'PUT';
		} else {
			$http_method = 'POST';
		}

		// start mailchimp api call. check for test mode first.
		$test_mode = filter_var( get_option( $this->option_prefix . 'test_mode', false ), FILTER_VALIDATE_BOOLEAN );
		if ( true !== $test_mode ) {
			$result = $this->parent->mailchimp->send( $resource_type . '/' . $resource_id . '/' . $subresource_type, $http_method, $params );
		} else {
			// by default, we just have a generic test result array. If necessary we could maybe allow the settings to choose what is populated.
			$result = array(
				'id'              => 8675309,
				'email_address'   => 'test@test.com',
				'unique_email_id' => md5( 'test@test.com' ),
				'contact_id'      => rand(),
				'full_name'       => 'Test Name',
				'web_id'          => rand(),
				'email_type'      => 'html',
				'status'          => 'subscribed',
				'method'          => 'POST',
			);
			/*$result = array(
				'email_address'   => 'test@test.com',
				'unique_email_id' => md5( 'test@test.com' ),
				'contact_id'      => rand(),
				'full_name'       => 'Test Name',
				'web_id'          => rand(),
				'email_type'      => 'html',
				'status'          => 408,
				'detail'          => 'Test Mailchimp error',
				'method'          => 'POST',
			);*/
		}

		// if a user has been unsubscribed and they filled out this form, set them to pending so they can confirm
		if ( isset( $result['status'] ) && 'unsubscribed' === $result['status'] ) {
			$params['status'] = 'pending';
			$http_method      = 'PUT';
			$result           = $this->parent->mailchimp->send( $resource_type . '/' . $resource_id . '/' . $subresource_type, $http_method, $params );
		}

		if ( '' !== $confirm_message ) {
			$result['confirm_message'] = $confirm_message;
		}

		return $result;
	}

}
