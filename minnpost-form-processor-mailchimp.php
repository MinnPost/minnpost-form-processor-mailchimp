<?php
/*
Plugin Name: MinnPost Form Procesor for MailChimp
Plugin URI:
Description:
Version: 0.0.1
Author: Jonathan Stegall
Author URI: http://code.minnpost.com
License: GPL2+
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: minnpost-form-processor-mailchimp
*/

if ( ! class_exists( 'Form_Processor_MailChimp' ) ) {
	die();
}

class Minnpost_Form_Processor_MailChimp extends Form_Processor_MailChimp {

	public $option_prefix;
	public $version;
	public $slug;

	private $api_key;
	private $resource_type;
	private $resource_id;
	private $subresource_type;

	/**
	 * @var object
	 * Static property to hold an instance of the class; this seems to make it reusable
	 *
	 */
	static $instance = null;

	/**
	* Load the static $instance property that holds the instance of the class.
	* This instance makes the class reusable by other plugins
	*
	* @return object
	*   The sfapi object if it is authenticated (empty, otherwise)
	*
	*/
	static public function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new Minnpost_Form_Processor_MailChimp();
		}
		return self::$instance;
	}

	public function __construct() {

		$this->version = '0.0.1';
		$this->slug = 'minnpost-form-processor-mailchimp';

		parent::__construct();

		// admin settings
		$this->admin = $this->load_admin();

		$this->api_key = $this->mailchimp->api_key;
		$this->resource_type = 'lists';
		$this->resource_id = '3631302e9c';
		$this->subresource_type = 'members';
		$this->user_field = 'interests';

		$this->add_actions();
	}

	private function add_actions() {
		add_shortcode( 'custom-account-preferences-form', array( $this, 'account_preferences_form' ), 10, 2 );
		add_filter( 'user_account_management_add_to_user_data', array( $this, 'add_to_mailchimp_data' ), 10, 3 );
		apply_filters( 'user_account_management_modify_user_data', array( $this, 'remove_mailchimp_from_user_data' ), 10, 1 );
		add_filter( 'user_account_management_pre_save_result', array( $this, 'user_mailchimp_list_settings' ), 10, 1 );
		add_filter( 'user_account_management_custom_error_message', array( $this, 'mailchimp_error_message' ), 10, 2 );
	}

	/**
	 * A shortcode for rendering the form used to change a logged in user's preferences
	 *
	 * @param  array   $attributes  Shortcode attributes.
	 * @param  string  $content     The text content for shortcode.
	 *
	 * @return string  The shortcode output
	 */
	public function account_preferences_form( $attributes, $content = null ) {

		if ( ! is_array( $attributes ) ) {
			$attributes = array();
		}

		$user_id = get_query_var( 'users', '' );
		if ( isset( $_GET['user_id'] ) ) {
			$user_id = esc_attr( $_GET['user_id'] );
		} else {
			$user_id = get_current_user_id();
		}

		$can_access = false;
		if ( class_exists( 'User_Account_Management' ) ) {
			$account_management = User_Account_Management::get_instance();
			$can_access = $account_management->check_user_permissions( $user_id );
		} else {
			return;
		}
		// if we are on the current user, or if this user can edit users
		if ( false === $can_access ) {
			return __( 'You do not have permission to access this page.', 'minnpost-largo' );
		}

		// this functionality is mostly from https://pippinsplugins.com/change-password-form-short-code/
		// we should use it for this page as well, unless and until it becomes insufficient

		$attributes['current_url'] = get_current_url();
		$attributes['redirect'] = $attributes['current_url'];

		if ( ! is_user_logged_in() ) {
			return __( 'You are not signed in.', 'user-account-management' );
		} else {
			//$attributes['login'] = rawurldecode( $_REQUEST['login'] );

			// translators: instructions on top of the form
			$attributes['instructions'] = sprintf( '<p class="a-form-instructions">' . esc_html__( 'If you have set up reading or email preferences, you can update them below.', 'minnpost-largo' ) . '</p>' );

			// Error messages
			$errors = array();
			if ( isset( $_REQUEST['errors'] ) ) {
				$error_codes = explode( ',', $_REQUEST['errors'] );

				foreach ( $error_codes as $code ) {
					$errors[] = $account_management->get_error_message( $code );
				}
			}
			$attributes['errors'] = $errors;
			if ( isset( $user_id ) && '' !== $user_id ) {
				$attributes['user'] = get_userdata( $user_id );
			} else {
				$attributes['user'] = wp_get_current_user();
			}
			$attributes['user_meta'] = get_user_meta( $attributes['user']->ID );

			$attributes['reading_topics'] = array(
				'Arts & Culture' => 'Arts & Culture',
				'Economy' => 'Economy',
				'Education' => 'Education',
				'Environment' => 'Environment',
				'Greater Minnesota news' => 'Greater Minnesota news',
				'Health' => 'Health',
				'MinnPost announcements' => 'MinnPost announcements',
				'Opinion/Commentary' => 'Opinion/Commentary',
				'Politics & Policy' => 'Politics & Policy',
				'Sports' => 'Sports',
			);

			$attributes['user_reading_topics'] = array();
			if ( isset( $attributes['user_meta']['_reading_topics'] ) ) {
				if ( is_array( maybe_unserialize( $attributes['user_meta']['_reading_topics'][0] ) ) ) {
					$topics = maybe_unserialize( $attributes['user_meta']['_reading_topics'][0] );
					foreach ( $topics as $topic ) {
						$attributes['user_reading_topics'][] = $topic;
					}
				}
			}

			return $account_management->get_template_html( 'account-preferences-form', 'front-end', $attributes );

		}
	}

	/**
	 * Adds form data for MailChimp settings to user data so it can be processed for MailChimp API
	 *
	 * @param  array   $user_data  The info we currently have on the user
	 * @param  array   $posted     The info posted to the form by the user
	 *
	 * @return array  $user_data
	 */
	public function add_to_mailchimp_data( $user_data, $posted ) {
		// mailchimp fields
		if ( isset( $posted['_newsletters'] ) ) {
			$user_data['_newsletters'] = $posted['_newsletters'];
		}
		if ( isset( $posted['_occasional_emails'] ) ) {
			$user_data['_occasional_emails'] = $posted['_occasional_emails'];
		}
		return $user_data;
	}


	/**
	 * Removes MailChimp settings from user meta so they aren't saved in WordPress
	 *
	 * @param  array   $user_data  The info submitted by the user
	 *
	 * @return array  $user_data
	 */
	public function remove_mailchimp_from_user_data( $user_data ) {
		// remove the mailchimp fields from the user data so it doesn't get saved into the usermeta table
		if ( isset( $user_data['_newsletters'] ) ) {
			unset( $user_data['_newsletters'] );
		}
		if ( isset( $user_data['_occasional_emails'] ) ) {
			unset( $user_data['_occasional_emails'] );
		}
		return $user_data;
	}


	/**
	 * Saves MailChimp settings before any data is saved in WordPress
	 *
	 * @param  array   $user_data  The info submitted by the user
	 *
	 * @return array  $result
	 */
	public function user_mailchimp_list_settings( $user_data ) {
		// before we update the user in WP, send their data to mailchimp and create/update their info
		$email = isset( $user_data['user_email'] ) ? $user_data['user_email'] : '';
		$first_name = isset( $user_data['first_name'] ) ? $user_data['first_name'] : '';
		$last_name = isset( $user_data['last_name'] ) ? $user_data['last_name'] : '';
		$newsletters = isset( $user_data['_newsletters'] ) ? $user_data['_newsletters'] : '';
		$occasional_emails = isset( $user_data['_occasional_emails'] ) ? $user_data['_occasional_emails'] : '';
		if ( '' === $newsletters && '' === $occasional_emails ) {
			return;
		}

		$params['email_address'] = $email;
		$params['status'] = 'subscribed';
		$params['merge_fields'] = array(
			'FNAME' => $first_name,
			'LNAME' => $last_name,
		);
		// default is false if it is not in this form
		// that is the only way we can remove a subscription option
		$all_newsletters = get_mailchimp_newsletter_options();
		foreach ( $all_newsletters as $key => $value ) {
			$params[ $this->user_field ][ $key ] = 'false';
		}
		$all_occasional_emails = get_mailchimp_occasional_email_options();
		foreach ( $all_occasional_emails as $key => $value ) {
			$params[ $this->user_field ][ $key ] = 'false';
		}

		// add the groups the user actually wants
		if ( ! empty( $newsletters ) ) {
			foreach ( $newsletters as $key => $value ) {
				$params[ $this->user_field ][ $value ] = 'true';
			}
		}
		if ( ! empty( $occasional_emails ) ) {
			foreach ( $occasional_emails as $key => $value ) {
				$params[ $this->user_field ][ $value ] = 'true';
			}
		}

		$result = $this->mailchimp->send( $this->resource_type . '/' . $this->resource_id . '/' . $this->subresource_type, 'PUT', $params );
		return $result;

		/*$params['body'] = array(
			'email_address' => $email,
			'status' => 'subscribed',
			'merge_fields[FNAME]' => $first_name,
			'merge_fields[LNAME]' => $last_name,
		);
		// default is false if it is not in this form
		// that is the only way we can remove a subscription option
		$all_newsletters = get_mailchimp_newsletter_options();
		foreach ( $all_newsletters as $key => $value ) {
			$params['body'][ 'interests[' . $key . ']' ] = 'false';
		}
		$all_occasional_emails = get_mailchimp_occasional_email_options();
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

		$params['method'] = 'PUT';
		$params['timeout'] = 30;
		$params['sslverify'] = false;

		$rest_url = site_url( '/wp-json/form-processor-mc/v1/' . $this->resource_type . '/' . $this->resource_id . '/' . $this->subresource_type . '/?api_key=' . $this->api_key );
		$result = wp_remote_request( $rest_url, $params );
		*/

	}


	/**
	 * Error messages for user pages
	 *
	 * @param  string   $message  Can take a default message
	 * @param  string   $error_code     The error identifier
	 *
	 * @return string  $message
	 */
	public function mailchimp_error_message( $message, $error_code ) {
		switch ( $error_code ) {
			case '_newsletters':
				$message = __( 'There was an error saving your newsletter choices. Please try again.', 'minnpost-largo' );
				break;
			case '_occasional_emails':
				$message = __( 'There was an error saving your occasional MinnPost email choices. Please try again.', 'minnpost-largo' );
				break;
			case 'mailchimp':
				$message = __( 'There was an error saving your email preferences. Please try again.', 'minnpost-largo' );
				break;
			default:
				break;
		}
		return $message;
	}

	/**
	 * Get current values for user's MailChimp settings
	 *
	 * @param  bool   $reset  Whether to skip the cache
	 *
	 * @return array  $checked
	 */
	public function get_mailchimp_user_values( $reset = false ) {
		// figure out if we have a current user and use their settings as the default selections
		// problem: if the user has a setting for this field, this default callback won't be called
		// solution: we should just never save this field. the mailchimp plugin's cache settings will keep from overloading the api
		$user_id = get_query_var( 'users', '' );
		if ( isset( $_GET['user_id'] ) ) {
			$user_id = esc_attr( $_GET['user_id'] );
		} else {
			$user_id = get_current_user_id();
		}

		if ( '' !== $user_id ) {
			$user = get_userdata( $user_id );
			$email = $user->user_email;

			$front_end = $this->front_end;
			$user_info = $front_end->get_user_info( $this->resource_id, $email, $reset );
			$user_interests = $user_info[ $this->user_field ];

			$checked = array();
			foreach ( $user_interests as $key => $interest ) {
				if ( 1 === absint( $interest ) ) {
					$checked[] = $key;
				}
			}
			return $checked;
		}
	}

	/**
	 * Get available values for user's MailChimp settings from MailChimp
	 *
	 * @param  string   $wp_field     What field in WordPress is going to hold the options
	 * @param  string   $category_id  Category ID in MailChimp
	 * @param  string   $mailchimp_field  Field that will store the values in MailChimp
	 *
	 * @return array  $options
	 */
	public function get_mailchimp_field_options( $wp_field, $category_id, $mailchimp_field = '' ) {
		if ( '' === $mailchimp_field ) {
			$mailchimp_field = $this->user_field;
		}
		$categories = $this->front_end->generate_interest_options( $this->resource_id, $category_id, $key );
		foreach ( $categories as $key => $category ) {
			$options = isset( $category[ $mailchimp_field ] ) ? $category[ $mailchimp_field ] : $category;
		}
		return $options;
	}


	/**
	* load the admin stuff
	* creates admin menu to save the config options
	*
	* @throws \Exception
	*/
	public function load_admin() {
		$admin = '';
		add_filter( 'plugin_action_links', array( $this, 'plugin_action_links' ), 10, 2 );
		return $admin;
	}

	/**
	* Display a Settings link on the main Plugins page
	*
	* @param array $links
	* @param string $file
	* @return array $links
	* These are the links that go with this plugin's entry
	*/
	public function plugin_action_links( $links, $file ) {
		if ( plugin_basename( __FILE__ ) === $file ) {
			$settings = '<a href="' . get_admin_url() . 'options-general.php?page=' . $this->slug . '">' . __( 'Settings', 'minnpost-form-processor-mailchimp' ) . '</a>';
			array_unshift( $links, $settings );
		}
		return $links;
	}

}

// Instantiate our class
$minnpost_form_processor_mailchimp = Minnpost_Form_Processor_MailChimp::get_instance();
