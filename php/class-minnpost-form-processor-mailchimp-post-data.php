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
	}

	/**
	* Process a user's submitted form data
	*
	*/
	public function process_form_data() {
		$action = isset( $_POST['action'] ) ? esc_attr( $_POST['action'] ) : '';
		if ( isset( $_POST['minnpost_form_processor_mailchimp_nonce'] ) && wp_verify_nonce( $_POST['minnpost_form_processor_mailchimp_nonce'], 'minnpost_form_processor_mailchimp_nonce' ) ) {

			// required form data
			$user_id = isset( $_POST['user_id'] ) ? esc_attr( $_POST['user_id'] ) : '';
			$status  = isset( $_POST['user_status'] ) ? esc_attr( $_POST['user_status'] ) : get_option( $this->option_prefix . $action . '_default_member_status' );
			$email   = isset( $_POST['email'] ) ? sanitize_email( $_POST['email'] ) : '';

			// this is the array of available mailchimp groups
			$groups_available = isset( $_POST['groups_available'] ) ? (array) array_map( 'esc_attr', $_POST['groups_available'] ) : get_option( $this->option_prefix . $action . '_default_mc_resource_items', array() );

			// this is the array of groups submitted by the user, if applicable
			$groups_submitted = isset( $_POST['groups_submitted'] ) ? (array) array_map( 'esc_attr', $_POST['groups_submitted'] ) : array();

			// if the user submitted groups, assign them the ones that are available to this form.
			if ( ! empty( $groups_submitted ) ) {
				$groups = array_intersect( $groups_submitted, $groups_available );
			} else {
				// otherwise, assign them whatever is available.
				$groups = $groups_available;
			}

			// optional form data
			$first_name      = isset( $_POST['first_name'] ) ? sanitize_text_field( $_POST['first_name'] ) : '';
			$last_name       = isset( $_POST['last_name'] ) ? sanitize_text_field( $_POST['last_name'] ) : '';
			$confirm_message = isset( $_POST['confirm_message'] ) ? wp_kses_post( wpautop( $_POST['confirm_message'] ) ) : '';

			// setup the mailchimp user array and add the required items to it
			$user_data = array(
				'user_id'     => $user_id,
				'user_email'  => $email,
				'user_status' => $status,
			);

			// set default mailchimp group settings based on the shortcode attributes and the plugin settings
			if ( '' !== $form['groups'] ) {
				if ( is_array( $form['groups'] ) ) {
					$groups = array_map( 'esc_attr', $form['groups'] );
				} else {
					$groups = esc_attr( $form['groups'] );
				}
			}
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

			error_log( 'user data is ' . print_r( $user_data, true ) );

			// mailchimp fields
			/*$result = minnpost_form_processor_mailchimp()->save_user_mailchimp_list_settings( $user_data );

			if ( isset( $result['id'] ) ) {
				if ( 'PUT' === $result['method'] ) {
					$user_status = 'existing';
				} elseif ( 'POST' === $result['method'] ) {
					$user_status = 'new';
					if ( 'pending' === $result['status'] ) {
						$user_status = 'pending';
					}
				}
				if ( isset( $_POST['ajaxrequest'] ) && 'true' === $_POST['ajaxrequest'] ) {
					wp_send_json_success(
						array(
							'id'              => $result['id'],
							'user_status'     => $user_status,
							'confirm_message' => $confirm_message,
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
					$redirect_url = add_query_arg( 'subscribe-message', 'success-' . $user_status, $redirect_url );

					wp_redirect( $redirect_url );
					exit;
				}
			}*/

			/*$params['body'] = array(
				'email_address' => $email,
				'status' => $status,
				'merge_fields[FNAME]' => $first_name,
				'merge_fields[LNAME]' => $last_name,
			);
			foreach ( $all_newsletters as $key => $value ) {
				$params['body'][ 'interests[' . $key . ']' ] = 'false';
			}
			foreach ( $all_occasional_emails as $key => $value ) {
				$params['body'][ 'interests[' . $key . ']' ] = 'false';
			}

			// add the groups the user actually wants
			if ( ! empty( $newsletters ) ) {
				foreach ( $newsletters as $key => $value ) {
					$params['body'][ 'interests[' . $value . ']' ] = 'true';
				}
			}
			if ( ! empty( $occasional_emails ) ) {
				foreach ( $occasional_emails as $key => $value ) {
					$params['body'][ 'interests[' . $value . ']' ] = 'true';
				}
			}

			$params['method'] = $http_method;
			$params['timeout'] = 30;
			$params['sslverify'] = false;

			$rest_url = site_url( '/wp-json/form-processor-mc/v1/lists/3631302e9c/members/?api_key=0334e149b481a2391cfdd428238358a9-us1' );
			$result = wp_remote_request( $rest_url, $params );*/

			//error_log( 'result is ' . print_r( $result, true ) );
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

}
