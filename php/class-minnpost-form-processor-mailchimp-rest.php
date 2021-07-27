<?php
/**
 * Class file for the MinnPost_Form_Processor_MailChimp_Rest class.
 *
 * @file
 */

if ( ! class_exists( 'MinnPost_Form_Processor_MailChimp' ) ) {
	die();
}

/**
 * Rest API methods
 */
class MinnPost_Form_Processor_MailChimp_Rest {

	public $option_prefix;
	public $parent_option_prefix;
	public $version;
	public $slug;
	public $get_data;
	public $post_data;

	/**
	* Constructor which sets up rest api
	*/
	public function __construct() {

		$this->option_prefix        = minnpost_form_processor_mailchimp()->option_prefix;
		$this->parent_option_prefix = minnpost_form_processor_mailchimp()->parent_option_prefix;
		$this->version              = minnpost_form_processor_mailchimp()->version;
		$this->slug                 = minnpost_form_processor_mailchimp()->slug;
		$this->get_data             = minnpost_form_processor_mailchimp()->get_data;
		$this->post_data            = minnpost_form_processor_mailchimp()->post_data;

		$this->rest_namespace = 'minnpost-api/v';
		$this->rest_version   = '2';

		add_action( 'rest_api_init', array( $this, 'register_routes' ) );

	}

	/**
	* Register REST API routes for the configured MailChimp objects
	*
	* @throws \Exception
	*/
	public function register_routes() {
		$namespace = $this->rest_namespace . $this->rest_version;

		register_rest_route(
			$namespace,
			'/mailchimp/user',
			array(
				array(
					'methods'             => array( WP_REST_Server::CREATABLE, WP_REST_Server::READABLE ),
					'callback'            => array( $this, 'process_user_request' ),
					'permission_callback' => array( $this, 'can_process' ),
					'args'                => array(
						'email' => array(
							//'required'    => true,
							//'type'        => 'string',
							'description' => 'The user\'s email address',
							//'format'      => 'email',
						),
					),
				),
			)
		);

		register_rest_route(
			$namespace,
			'/mailchimp/form',
			array(
				array(
					'methods'             => array( WP_REST_Server::READABLE ),
					'callback'            => array( $this, 'process_form_request' ),
					'permission_callback' => array( $this, 'can_process' ),
					'args'                => array(
						'shortcode'        => array(
							//'required'    => true,
							//'type'        => 'string',
							'description' => 'The form\'s shortcode name',
							//'format'      => 'email',
						),
						'groups_available' => array(
							//'required'    => true,
							//'type'        => 'string',
							'description' => 'The groups that should be available to this form',
							//'format'      => 'email',
						),
					),
				),
			)
		);
	}

	/**
	* Process the REST API request for the user endpoint
	*
	* @return $result
	*/
	public function process_user_request( WP_REST_Request $request ) {
		$http_method           = $request->get_method();
		$shortcode             = 'newsletter_form'; // todo: we could make this configurable somehow?
		$resource_type         = $this->get_data->get_resource_type( $shortcode );
		$resource_id           = $this->get_data->get_resource_id( $shortcode );
		$subresource_type      = $this->get_data->get_subresource_type( $shortcode );
		$user_mailchimp_groups = get_option( $this->option_prefix . $shortcode . '_mc_resource_item_type', '' );

		switch ( $http_method ) {
			case 'GET':
				$user_email      = $request->get_param( 'email' );
				$reset_user_info = true;

				$result = $this->get_data->get_user_info( $shortcode, $resource_type, $resource_id, $user_email, $reset_user_info );

				$user = array();
				if ( ! is_wp_error( $result ) ) {
					$user['mailchimp_user_id'] = $result['id'];
					$user['groups']            = $result[ $user_mailchimp_groups ];
					$user['mailchimp_status']  = $result['status'];
				} else {
					return $result;
				}

				return $user;
				break;
			case 'POST':
				// required form data
				$mailchimp_user_id = $request->get_param( 'mailchimp_user_id' );
				$status            = $request->get_param( 'mailchimp_status' );
				$email             = $request->get_param( 'email' );

				// this is the mailchimp group settings field. it gets sanitized later.
				$groups_available = $request->get_param( 'groups_available' );

				// this checks for allowed groups based on the settings
				$groups_available = $this->get_data->get_shortcode_groups( $shortcode, $resource_type, $resource_id, $groups_available );

				// this is the array of groups submitted by the user, if applicable
				$groups_submitted = $request->get_param( 'groups_submitted' );

				// if the user submitted groups, assign them the ones that are available to this form.
				// note: submitted needs to be an array of keys. whatever else available contains (ids, default, etc.) it needs to also have the id as the a column of the array.
				if ( ! empty( $groups_submitted ) ) {
					$groups = array_intersect( $groups_submitted, array_column( $groups_available, 'id' ) );
				} else {
					// otherwise, assign them whatever is available based on settings. we only need the ids.
					$groups = array_column( $groups_available, 'id' );
				}

				// optional form data
				$first_name      = $request->get_param( 'first_name' );
				$last_name       = $request->get_param( 'last_name' );
				$confirm_message = $request->get_param( 'confirm_message' );

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

				// send data to plugin
				$result = $this->post_data->save_to_mailchimp( $shortcode, $resource_type, $resource_id, $subresource_type, $user_data );
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
	* Process the REST API request for the form endpoint
	*
	* @return $result
	*/
	public function process_form_request( WP_REST_Request $request ) {
		$http_method           = $request->get_method();
		$shortcode             = $request->get_param( 'shortcode' );
		$resource_type         = $this->get_data->get_resource_type( $shortcode );
		$resource_id           = $this->get_data->get_resource_id( $shortcode );
		$subresource_type      = $this->get_data->get_subresource_type( $shortcode );
		$user_mailchimp_groups = get_option( $this->option_prefix . $shortcode . '_mc_resource_item_type', '' );
		$groups_available      = $request->get_param( 'groups_available' );
		$placement             = $request->get_param( 'placement' );
		if ( ! isset( $groups_available ) ) {
			$groups_available = 'all';
		}
		if ( ! isset( $placement ) ) {
			$placement = '';
		}
		// this checks for allowed groups based on the settings
		$groups         = $this->get_data->get_shortcode_groups( $shortcode, $resource_type, $resource_id, $groups_available );
		$group_fields   = $this->get_data->get_shortcode_groups( $shortcode, $resource_type, $resource_id, $groups_available, $placement );
		$groups         = array_column( $groups, 'id' );
		$shortcode_data = array(
			'groups_available' => $groups_available,
			'group_fields'     => $group_fields,
		);
		return $shortcode_data;
	}

	/**
	 * Check to see if the user has permission to do this
	 *
	 * @param WP_REST_Request $request the request object sent to the API.
	 */
	public function can_process( WP_REST_Request $request ) {
		// unless we specify otherwise, the method should return true.
		$http_method = $request->get_method();
		$class       = $request->get_url_params()['class'];
		return true;
	}

}
