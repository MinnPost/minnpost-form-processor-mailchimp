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
	//public $parent;

	/**
	* Constructor which sets up post data processing
	*/
	public function __construct() {
		$this->option_prefix        = minnpost_form_processor_mailchimp()->option_prefix;
		$this->parent_option_prefix = minnpost_form_processor_mailchimp()->parent_option_prefix;
		$this->version              = minnpost_form_processor_mailchimp()->version;
		$this->slug                 = minnpost_form_processor_mailchimp()->slug;
		$this->get_data             = minnpost_form_processor_mailchimp()->get_data;
		//$this->parent               = minnpost_form_processor_mailchimp()->parent;

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

			// todo: error handling for this?
			$resource_type = $this->get_data->get_resource_type( $action );
			$resource_id   = $this->get_data->get_resource_id( $action );

			// placement of this form
			$placement = isset( $_POST['placement'] ) ? esc_attr( $_POST['placement'] ) : '';

			// required form data
			$user_id = isset( $_POST['user_id'] ) ? esc_attr( $_POST['user_id'] ) : '';
			$status  = isset( $_POST['user_status'] ) ? esc_attr( $_POST['user_status'] ) : get_option( $this->option_prefix . $action . '_default_member_status' );
			$email   = isset( $_POST['email'] ) ? sanitize_email( $_POST['email'] ) : '';

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
				'user_id'     => $user_id,
				'user_email'  => $email,
				'user_status' => $status,
			);

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

			error_log( 'send to mailchimp: ' . print_r( $user_data, true ) );

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
