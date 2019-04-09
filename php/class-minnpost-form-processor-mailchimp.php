<?php
/**
 * The main plugin class
 *
 * @package MinnPost_Form_Processor_MailChimp
 */

if ( ! class_exists( 'Form_Processor_MailChimp' ) ) {
	return;
}

class MinnPost_Form_Processor_MailChimp {

	/**
	 * The version number for this release of the plugin.
	 * This will later be used for upgrades and enqueuing files
	 *
	 * This should be set to the 'Plugin Version' value defined
	 * in the plugin header.
	 *
	 * @var string A PHP-standardized version number string
	 */
	public $version;

	/**
	 * Filesystem path to the main plugin file
	 * @var string
	 */
	public $file;

	/**
	* @var object
	* Load the parent plugin
	*/
	public $parent;

	/**
	 * Prefix for plugin options in the parent plugin
	 * @var string
	 */
	public $parent_option_prefix;

	/**
	 * Prefix for plugin options
	 * @var string
	 */
	public $option_prefix;

	/**
	 * Plugin slug
	 * @var string
	 */
	public $slug;

	/**
	* @var object
	* Load the MailChimp API wrapper from the parent
	*/
	public $mailchimp;

	/**
	* @var object
	* Methods to get data for use by the plugin and other places
	*/
	public $get_data;

	/**
	* @var object
	* Shortcodes
	*/
	public $shortcodes;

	/**
	* @var object
	* Methods to handle post data
	*/
	public $post_data;

	/**
	* @var object
	* Administrative interface
	*/
	public $admin;


	private $resource_type;
	private $resource_id;
	private $user_subresource_type;
	private $list_subresource_type;

	/**
	 * Class constructor
	 *
	 * @param string $version The current plugin version
	 * @param string $file The main plugin file
	 */
	public function __construct( $version, $file ) {

		$this->version       = $version;
		$this->file          = $file;
		$this->option_prefix = 'minnpost_form_processor_mailchimp_';
		// parent plugin
		$this->parent               = $this->load_parent();
		$this->parent_option_prefix = $this->parent->option_prefix;
		$this->slug                 = 'minnpost-form-processor-mailchimp';

	}

	public function init() {

		// mailchimp api
		$this->mailchimp = $this->parent->mailchimp;

		// Get data and make it available
		$this->get_data = new MinnPost_Form_Processor_MailChimp_Get_Data();

		// Shortcodes
		$this->shortcodes = new MinnPost_Form_Processor_MailChimp_Shortcodes();

		// Handle post data
		$this->post_data = new MinnPost_Form_Processor_MailChimp_Post_Data();

		// REST API
		$this->rest = new MinnPost_Form_Processor_MailChimp_Rest();

		// Admin features
		$this->admin = new MinnPost_Form_Processor_MailChimp_Admin();

		add_action( 'plugins_loaded', array( $this, 'add_actions' ) );

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
			require_once plugin_dir_path( $this->file ) . '../form-processor-mailchimp/form-processor-mailchimp.php';
			$plugin = form_processor_mailchimp();
			return $plugin;
		}
	}

	/**
	* Do actions
	*
	*/
	public function add_actions() {
		add_filter( 'user_account_management_add_to_user_data', array( $this, 'add_to_mailchimp_data' ), 10, 3 );
		apply_filters( 'user_account_management_modify_user_data', array( $this, 'remove_mailchimp_from_user_data' ), 10, 1 );
		add_filter( 'user_account_management_pre_save_result', array( $this, 'save_user_mailchimp_list_settings' ), 10, 1 );
		add_filter( 'user_account_management_post_user_data_save', array( $this, 'save_user_meta' ), 10, 1 );
		add_filter( 'user_account_management_custom_error_message', array( $this, 'mailchimp_error_message' ), 10, 2 );
	}

	/**
	 * Get the URL to the plugin admin menu
	 *
	 * @return string          The menu's URL
	 */
	public function get_menu_url() {
		$url = 'options-general.php?page=' . $this->slug;
		return admin_url( $url );
	}

	/**
	 * Load up the localization file if we're using WordPress in a different language.
	 *
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'minnpost-form-processor-mailchimp', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
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
		$all_newsletters = $this->get_data->get_mailchimp_field_options( '_newsletters', $this->newsletters_id );
		if ( is_array( $all_newsletters ) ) {
			foreach ( $all_newsletters as $key => $value ) {
				if ( ( ! isset( $user_data['newsletters_available'] ) && ! isset( $user_data['occasional_emails_available'] ) ) || ( isset( $user_data['newsletters_available'] ) && in_array( $key, $user_data['newsletters_available'] ) ) ) {
					$params[ $this->user_field ][ $key ] = 'false';
				}
			}
		}

		$all_occasional_emails = $this->get_data->get_mailchimp_field_options( '_occasional_emails', $this->occasional_emails_id );
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

			$user_info = $this->get_data->get_user_info( $this->resource_id, $email, $reset );

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

}
