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

	/**
	* Constructor which sets up rest api
	*/
	public function __construct() {

		$this->option_prefix        = minnpost_form_processor_mailchimp()->option_prefix;
		$this->parent_option_prefix = minnpost_form_processor_mailchimp()->parent_option_prefix;
		$this->version              = minnpost_form_processor_mailchimp()->version;
		$this->slug                 = minnpost_form_processor_mailchimp()->slug;
		$this->get_data             = minnpost_form_processor_mailchimp()->get_data;

		$this->rest_namespace = 'minnpost-api/v';
		$this->rest_version   = '1';

		add_action( 'rest_api_init', array( $this, 'register_routes' ) );

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
				$newsletters = $this->get_data->get_mailchimp_field_options( '_newsletters', $this->newsletters_id );
				$interests   = $this->get_data->get_mailchimp_field_options( '_occasional_emails', $this->occasional_emails_id );
				$options     = array_merge( $newsletters, $interests );

				$email  = $request->get_param( 'email' );
				$result = $this->get_data->get_user_info( $this->resource_id, md5( strtolower( $email ) ), true );
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

}
