<?php
/*
Plugin Name: MinnPost Form Procesor for MailChimp
Plugin URI:
Description:
Version: 0.0.5
Author: Jonathan Stegall
Author URI: https://code.minnpost.com
License: GPL2+
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: minnpost-form-processor-mailchimp
*/

if ( ! class_exists( 'Form_Processor_MailChimp' ) ) {
	return;
}

class Minnpost_Form_Processor_MailChimp {

	public $option_prefix;
	public $parent_option_prefix;
	public $version;
	public $slug;

	private $api_key;
	private $resource_type;
	private $resource_id;
	private $user_subresource_type;
	private $list_subresource_type;

	/**
	* @var object
	* Load the parent plugin
	*/
	public $parent;

	/**
	* @var object
	* Load the MailChimp API wrapper from the parent
	*/
	public $mailchimp;

	/**
	* @var object
	* Administrative interface
	*/
	public $admin;

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
	*   new instance of the plugin
	*
	*/
	static public function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new Minnpost_Form_Processor_MailChimp();
		}
		return self::$instance;
	}

	public function __construct() {

		$this->option_prefix        = 'minnpost_form_processor_mailchimp_';
		$this->parent_option_prefix = 'form_process_mc_';
		$this->version              = '0.0.5';
		$this->slug                 = 'minnpost-form-processor-mailchimp';
		$this->plugin_file          = __FILE__;

		// parent plugin
		$this->parent = $this->load_parent();

		// mailchimp api
		$this->mailchimp = $this->parent->mailchimp;


		// admin settings
		$this->admin = $this->load_admin();

		$this->rest_namespace = 'minnpost-api/v';
		$this->rest_version   = '1';

		add_action( 'plugins_loaded', array( $this, 'add_actions' ) );
	}

	/**
	* Do actions
	*
	*/
		add_shortcode( 'custom-account-preferences-form', array( $this, 'account_preferences_form' ), 10, 2 );
	public function add_actions() {
		add_filter( 'user_account_management_add_to_user_data', array( $this, 'add_to_mailchimp_data' ), 10, 3 );
		apply_filters( 'user_account_management_modify_user_data', array( $this, 'remove_mailchimp_from_user_data' ), 10, 1 );
		add_filter( 'user_account_management_pre_save_result', array( $this, 'save_user_mailchimp_list_settings' ), 10, 1 );
		add_filter( 'user_account_management_post_user_data_save', array( $this, 'save_user_meta' ), 10, 1 );
		add_filter( 'user_account_management_custom_error_message', array( $this, 'mailchimp_error_message' ), 10, 2 );
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	* Load and set values we don't need until the parent plugin is actually loaded
	*
	*/
	public function load_parent() {
		// get the base class
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
		}
		if ( is_plugin_active( 'form-processor-mailchimp/form-processor-mailchimp.php' ) ) {
			require_once plugin_dir_path( $this->plugin_file ) . '../form-processor-mailchimp/form-processor-mailchimp.php';
			$plugin = form_processor_mailchimp();
			return $plugin;
		}
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
			$can_access         = $account_management->check_user_permissions( $user_id );
		} else {
			return;
		}
		// if we are on the current user, or if this user can edit users
		if ( false === $can_access ) {
			return __( 'You do not have permission to access this page.', 'minnpost-form-processor-mailchimp' );
		}

		// this functionality is mostly from https://pippinsplugins.com/change-password-form-short-code/
		// we should use it for this page as well, unless and until it becomes insufficient

		$attributes['current_url'] = get_current_url();
		$attributes['redirect']    = $attributes['current_url'];

		if ( ! is_user_logged_in() ) {
			return __( 'You are not signed in.', 'minnpost-form-processor-mailchimp' );
		} else {
			//$attributes['login'] = rawurldecode( $_REQUEST['login'] );

			// translators: instructions on top of the form
			$attributes['instructions'] = sprintf( '<p class="a-form-instructions">' . esc_html__( 'If you have set up reading or email preferences, you can update them below.', 'minnpost-form-processor-mailchimp' ) . '</p>' );

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

			// todo: this should probably be in the database somewhere
			$attributes['reading_topics'] = array(
				'Arts & Culture'         => __( 'Arts & Culture', 'minnpost-form-processor-mailchimp' ),
				'Economy'                => __( 'Economy', 'minnpost-form-processor-mailchimp' ),
				'Education'              => __( 'Education', 'minnpost-form-processor-mailchimp' ),
				'Environment'            => __( 'Environment', 'minnpost-form-processor-mailchimp' ),
				'Greater Minnesota news' => __( 'Greater Minnesota news', 'minnpost-form-processor-mailchimp' ),
				'Health'                 => __( 'Health', 'minnpost-form-processor-mailchimp' ),
				'MinnPost announcements' => __( 'MinnPost announcements', 'minnpost-form-processor-mailchimp' ),
				'Opinion/Commentary'     => __( 'Opinion/Commentary', 'minnpost-form-processor-mailchimp' ),
				'Politics & Policy'      => __( 'Politics & Policy', 'minnpost-form-processor-mailchimp' ),
				'Sports'                 => __( 'Sports', 'minnpost-form-processor-mailchimp' ),
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
	/**
	* load the admin stuff
	* creates admin menu to save the config options
	*
	* @throws \Exception
	*/
	public function load_admin() {
		require_once( plugin_dir_path( $this->plugin_file ) . 'classes/class-' . $this->slug . '-admin.php' );
		$admin = new Minnpost_Form_Processor_MailChimp_Admin( $this->option_prefix, $this->parent_option_prefix, $this->version, $this->slug, $this->plugin_file, $this->parent );
		return $admin;
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
		if ( isset( $posted['_mailchimp_user_id'] ) ) {
			$user_data['_mailchimp_user_id'] = esc_attr( $posted['_mailchimp_user_id'] );
		}
		if ( isset( $posted['mailchimp_user_status'] ) ) {
			$user_data['_mailchimp_user_status'] = esc_attr( $posted['mailchimp_user_status'] );
		}
		if ( isset( $posted['_newsletters'] ) ) {
			$user_data['_newsletters'] = array_map( 'esc_attr', $posted['_newsletters'] );
		}
		if ( isset( $posted['_occasional_emails'] ) ) {
			$user_data['_occasional_emails'] = array_map( 'esc_attr', $posted['_occasional_emails'] );
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
		// we want to keep the user id so we can use it elsewhere, especially since it doesn't change from other systems when user changes their preferences
		if ( isset( $user_data['_mailchimp_user_status'] ) ) {
			unset( $user_data['_mailchimp_user_status'] );
		}
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
	public function save_user_mailchimp_list_settings( $user_data ) {
		// before we update the user in WP, send their data to mailchimp and create/update their info
		$id                = isset( $user_data['_mailchimp_user_id'] ) ? $user_data['_mailchimp_user_id'] : '';
		$status            = isset( $user_data['_mailchimp_user_status'] ) ? $user_data['_mailchimp_user_status'] : $this->user_default_new_status;
		$email             = isset( $user_data['user_email'] ) ? $user_data['user_email'] : '';
		$first_name        = isset( $user_data['first_name'] ) ? $user_data['first_name'] : '';
		$last_name         = isset( $user_data['last_name'] ) ? $user_data['last_name'] : '';
		$newsletters       = isset( $user_data['_newsletters'] ) ? $user_data['_newsletters'] : array();
		$occasional_emails = isset( $user_data['_occasional_emails'] ) ? $user_data['_occasional_emails'] : array();

		// don't send any data to mailchimp if there are no settings, and there is no user id
		// otherwise we need to, in case user wants to empty their preferences
		if ( empty( $newsletters ) && empty( $occasional_emails ) && '' === $id ) {
			return;
		}

		$params['email_address'] = $email;
		$params['status']        = $status;
		$params['merge_fields']  = array(
			'FNAME' => $first_name,
			'LNAME' => $last_name,
		);

		// we can allow forms to specify themselves to only be relevant to specific emails, otherwise it needs all of them
		// default is false if it is not allowed in the submitted form
		// that is the only way we can remove a subscription option if a user chooses to uncheck it
		$all_newsletters = $this->get_mailchimp_field_options( '_newsletters', $this->newsletters_id );
		if ( is_array( $all_newsletters ) ) {
			foreach ( $all_newsletters as $key => $value ) {
				if ( ( ! isset( $user_data['newsletters_available'] ) && ! isset( $user_data['occasional_emails_available'] ) ) || ( isset( $user_data['newsletters_available'] ) && in_array( $key, $user_data['newsletters_available'] ) ) ) {
					$params[ $this->user_field ][ $key ] = 'false';
				}
			}
		}

		$all_occasional_emails = $this->get_mailchimp_field_options( '_occasional_emails', $this->occasional_emails_id );
		if ( is_array( $all_occasional_emails ) ) {
			foreach ( $all_occasional_emails as $key => $value ) {
				if ( ( ! isset( $user_data['newsletters_available'] ) && ! isset( $user_data['occasional_emails_available'] ) ) || ( isset( $user_data['occasional_emails_available'] ) && in_array( $key, $user_data['occasional_emails_available'] ) ) ) {
					$params[ $this->user_field ][ $key ] = 'false';
				}
			}
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

		if ( '' !== $id ) {
			$http_method = 'PUT';
		} else {
			$http_method = 'POST';
		}

		$result = $this->mailchimp->send( $this->resource_type . '/' . $this->resource_id . '/' . $this->user_subresource_type, $http_method, $params );
		return $result;

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

		$rest_url = site_url( '/wp-json/form-processor-mc/v1/' . $this->resource_type . '/' . $this->resource_id . '/' . $this->user_subresource_type . '/?api_key=' . $this->api_key );
		$result = wp_remote_request( $rest_url, $params );
		*/

	}

	/**
	 * Saves any necessary items to the user's metadata
	 *
	 * @param  array   $user_data  The info submitted by the user
	 */
	public function save_user_meta( $user_data ) {
		if ( isset( $user_data['_mailchimp_user_id'] ) && '' !== $user_data['_mailchimp_user_id'] ) {
			update_user_meta( $user_data['ID'], '_mailchimp_user_id', $user_data['_mailchimp_user_id'] );
		}
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
				$message = __( 'There was an error saving your newsletter choices. Please try again.', 'minnpost-form-processor-mailchimp' );
				break;
			case '_occasional_emails':
				$message = __( 'There was an error saving your occasional MinnPost email choices. Please try again.', 'minnpost-form-processor-mailchimp' );
				break;
			case 'mailchimp':
				$message = __( 'There was an error saving your email preferences. Please try again.', 'minnpost-form-processor-mailchimp' );
				break;
			default:
				break;
		}
		return $message;
	}

	/**
	* Register REST API routes for the configured MailChimp objects
	*
	* @throws \Exception
	*/
	public function register_routes() {
		$namespace = $this->rest_namespace . $this->rest_version;

		register_rest_route( $namespace, '/mailchimp/user', array(
			array(
				'methods'  => array( WP_REST_Server::CREATABLE, WP_REST_Server::READABLE ),
				'callback' => array( $this, 'process_rest' ),
				'args'     => array(
					'email' => array(
						//'required'    => true,
						//'type'        => 'string',
						'description' => 'The user\'s email address',
						//'format'      => 'email',
					),
				),
			),
		) );
	}

	/**
	* Process the REST API request
	*
	* @return $result
	*/
	public function process_rest( WP_REST_Request $request ) {
		$http_method = $request->get_method();

		switch ( $http_method ) {
			case 'GET':
				$newsletters = $this->get_mailchimp_field_options( '_newsletters', $this->newsletters_id );
				$interests   = $this->get_mailchimp_field_options( '_occasional_emails', $this->occasional_emails_id );
				$options     = array_merge( $newsletters, $interests );

				$email  = $request->get_param( 'email' );
				$result = $this->get_user_info( $this->resource_id, md5( strtolower( $email ) ), true );
				if ( is_object( $result ) && array_key_exists( 404, $result->errors ) ) {
					return '';
				}
				if ( is_wp_error( $result ) ) {
					return $result;
				}
				$user = array(
					'status' => $result['status'],
				);
				if ( ! is_object( $result ) && 'subscribed' === $result['status'] ) {
					$user['mailchimp_id'] = $result['id'];
					$user['interests']    = array_intersect_key( $result['interests'], $options );
				} else {
					foreach ( $options as $key => $value ) {
						$user['interests'][ $key ] = false;
					}
				}
				return $user;
				break;
			case 'POST':
				$id         = $request->get_param( 'mailchimp_user_id' );
				$status     = $request->get_param( 'mailchimp_user_status' );
				$email      = $request->get_param( 'email' );
				$first_name = $request->get_param( 'first_name' );
				$last_name  = $request->get_param( 'last_name' );

				$newsletters       = $request->get_param( 'newsletters' );
				$occasional_emails = $request->get_param( 'occasional_emails' );

				$newsletters_available       = $request->get_param( 'newsletters_available' );
				$occasional_emails_available = $request->get_param( 'occasional_emails_available' );

				$user_data = array(
					'user_email' => $email,
					'first_name' => $first_name,
					'last_name'  => $last_name,
				);

				if ( null !== $id ) {
					$user_data['_mailchimp_user_id'] = $id;
				}
				if ( null !== $status ) {
					$user_data['_mailchimp_user_status'] = $status;
				}

				if ( ! empty( $newsletters_available ) ) {
					$user_data['newsletters_available'] = $newsletters_available;
				}
				if ( ! empty( $occasional_emails_available ) ) {
					$user_data['occasional_emails_available'] = $occasional_emails_available;
				}

				if ( ! empty( $newsletters ) ) {
					$user_data['_newsletters'] = $newsletters;
				}
				if ( ! empty( $newsletters ) ) {
					$user_data['_occasional_emails'] = $occasional_emails;
				}

				$result = $this->save_user_mailchimp_list_settings( $user_data );
				if ( is_wp_error( $result ) ) {
					return $result;
				}
				$user = array(
					'user_status'  => $result['status'],
					'mailchimp_id' => $result['id'],
				);
				return $user;
				break;
			default:
				return;
				break;
		}
		return;
	}

	/**
	 * Get current values for user's MailChimp settings
	 *
	 * @param  bool   $reset  Whether to skip the cache
	 *
	 * @return array  $checked
	 */
	public function get_mailchimp_user_values( $reset = false, $email = '' ) {
		// figure out if we have a current user and use their settings as the default selections
		// problem: if the user has a setting for this field, this default callback won't be called
		// solution: we should just never save this field. the mailchimp plugin's cache settings will keep from overloading the api

		$user_id = get_query_var( 'users', '' );
		if ( isset( $_GET['user_id'] ) ) {
			$user_id = esc_attr( $_GET['user_id'] );
		} else {
			$user_id = get_current_user_id();
		}

		if ( ( '' !== $user_id && 0 !== $user_id ) || '' !== $email ) {
			if ( '' !== $user_id && 0 !== $user_id ) {
				$user  = get_userdata( $user_id );
				$email = $user->user_email;
			}

			$user_info = $this->get_user_info( $this->resource_id, $email, $reset );

			if ( is_wp_error( $user_info ) ) {
				return array();
			}

			if ( isset( $user_info['id'] ) ) {
				$mailchimp_user['id'] = $user_info['id'];
			}
			if ( isset( $user_info['status'] ) ) {
				$mailchimp_user['status'] = $user_info['status'];
			}

			$user_interests = isset( $user_info[ $this->user_field ] ) ? $user_info[ $this->user_field ] : array();

			$mailchimp_user['checked'] = array();
			foreach ( $user_interests as $key => $interest ) {
				if ( 1 === absint( $interest ) ) {
					$mailchimp_user['checked'][] = $key;
				}
			}
			return $mailchimp_user;
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
		$categories = $this->generate_interest_options( $this->resource_id, $category_id, $wp_field );
		foreach ( $categories as $key => $category ) {
			$options = isset( $category[ $mailchimp_field ] ) ? $category[ $mailchimp_field ] : $category;
		}
		return $options;
	}

	/**
	* Get a user's information from MailChimp
	*
	* @param string $list_id
	* @param string $email
	* @param bool $reset
	* @return array $user
	*/
	private function get_user_info( $list_id, $email, $reset = false ) {
		// email needs to be lowercase before being hashed
		// see: https://developer.mailchimp.com/documentation/mailchimp/guides/manage-subscribers-with-the-mailchimp-api/
		/*
		In previous versions of the API, we exposed internal database IDs eid and leid for emails and list/email combinations. In API 3.0, we no longer use or expose either of these IDs. Instead, we identify your subscribers by the MD5 hash of the lowercase version of their email address so you can easily predict the API URL of a subscriber’s data.
		*/
		if ( is_email( $email ) ) {
			$email = md5( strtolower( $email ) );
		}
		$user = $this->mailchimp->load( $this->resource_type . '/' . $list_id . '/' . $this->user_subresource_type . '/' . $email, array(), $reset );

		if ( isset( $user['status'] ) && 404 !== $user['status'] ) {
			return $user;
		}
		$status = isset( $user['status'] ) ? $user['status'] : 'status missing';
		$detail = isset( $user['detail'] ) ? $user['detail'] : 'detail missing';
		return new WP_Error( $status, $detail );
	}

	/**
	* Generate an array of MailChimp interest options
	*
	* @param string $list_id
	* @param string $category_id
	* @param array $keys
	* @param string field_value
	* @return array $interest_options
	*/
	private function generate_interest_options( $list_id, $category_id = '', $keys = array(), $field_value = 'id' ) {
		// need to try to generate a field this way i think
		$interest_options = array();

		$resource_type    = $this->resource_type;
		$subresource_type = $this->list_subresource_type;
		$method           = $this->user_field;

		$params = array(
			'resource_type'    => $resource_type,
			'resource'         => $list_id,
			'subresource_type' => $subresource_type,
			'method'           => $method,
		);

		if ( '' === $category_id ) {
			$interest_categories = $this->mailchimp->load( $resource_type . '/' . $list_id . '/' . $subresource_type );
			foreach ( $interest_categories['categories'] as $key => $category ) {
				$id                                   = $category['id'];
				$title                                = $category['title'];
				$params['subresource']                = $id;
				$interests                            = $this->mailchimp->load( $resource_type . '/' . $list_id . '/' . $subresource_type . '/' . $category_id . '/' . $method, $params );
				$id                                   = isset( $keys[ $key ] ) ? $keys[ $key ] : $category['id'];
				$interest_options[ $id ]['title']     = $title;
				$interest_options[ $id ]['interests'] = array();
				if ( is_array( $interests['interests'] ) ) {
					foreach ( $interests['interests'] as $interest ) {
						$interest_id   = $interest['id'];
						$interest_name = $interest['name'];
						$interest_options[ $id ]['interests'][ ${'interest_' . $field_value} ] = $interest_name;
					}
				}
			}
		} else {
			$params['subresource'] = $category_id;
			$interests             = $this->mailchimp->load( $resource_type . '/' . $list_id . '/' . $subresource_type . '/' . $category_id . '/' . $method, $params );
			if ( is_array( $interests['interests'] ) ) {
				foreach ( $interests['interests'] as $interest ) {
					$interest_id   = $interest['id'];
					$interest_name = $interest['name'];
					$interest_options['interests'][ ${'interest_' . $field_value} ] = $interest_name;
				}
			}
		}

		return $interest_options;
	}

}

// Instantiate our class
$minnpost_form_processor_mailchimp = Minnpost_Form_Processor_MailChimp::get_instance();
