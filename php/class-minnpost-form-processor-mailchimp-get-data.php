<?php
/**
 * Class file for the MinnPost_Form_Processor_MailChimp_Get_Data class.
 *
 * @file
 */

if ( ! class_exists( 'MinnPost_Form_Processor_MailChimp' ) ) {
	die();
}

/**
 * Get MailChimp Data
 */
class MinnPost_Form_Processor_MailChimp_Get_Data {

	public $option_prefix;
	public $parent_option_prefix;
	public $version;
	public $slug;
	public $parent;

	/**
	* Constructor which gets data and makes it available
	*/
	public function __construct() {

		$this->option_prefix        = minnpost_form_processor_mailchimp()->option_prefix;
		$this->parent_option_prefix = minnpost_form_processor_mailchimp()->parent_option_prefix;
		$this->version              = minnpost_form_processor_mailchimp()->version;
		$this->slug                 = minnpost_form_processor_mailchimp()->slug;
		$this->parent               = minnpost_form_processor_mailchimp()->parent;

	}

	/**
	* Get a user's information from MailChimp
	*
	* @param string $resource_id
	* @param string $email
	* @param bool $reset
	* @return array $user
	*/
	public function get_user_info( $resource_id, $email, $reset = false ) {
		// email needs to be lowercase before being hashed
		// see: https://developer.mailchimp.com/documentation/mailchimp/guides/manage-subscribers-with-the-mailchimp-api/
		/*
		In previous versions of the API, we exposed internal database IDs eid and leid for emails and list/email combinations. In API 3.0, we no longer use or expose either of these IDs. Instead, we identify your subscribers by the MD5 hash of the lowercase version of their email address so you can easily predict the API URL of a subscriber’s data.
		*/
		if ( is_email( $email ) ) {
			$email = md5( strtolower( $email ) );
		}
		$user = $this->mailchimp->load( $this->resource_type . '/' . $resource_id . '/' . $this->user_subresource_type . '/' . $email, array(), $reset );

		if ( isset( $user['status'] ) && 404 !== $user['status'] ) {
			return $user;
		}
		$status = isset( $user['status'] ) ? $user['status'] : 'status missing';
		$detail = isset( $user['detail'] ) ? $user['detail'] : 'detail missing';
		return new WP_Error( $status, $detail );
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
	* Generate an array of MailChimp interest options
	*
	* @param string $resource_id
	* @param string $category_id
	* @param array $keys
	* @param string field_value
	* @return array $interest_options
	*/
	private function generate_interest_options( $resource_id, $category_id = '', $keys = array(), $field_value = 'id' ) {
		// need to try to generate a field this way i think
		$interest_options = array();

		$resource_type    = $this->resource_type;
		$subresource_type = $this->list_subresource_type;
		$method           = $this->user_field;

		$params = array(
			'resource_type'    => $resource_type,
			'resource'         => $resource_id,
			'subresource_type' => $subresource_type,
			'method'           => $method,
		);

		if ( '' === $category_id ) {
			$interest_categories = $this->parent->mailchimp->load( $resource_type . '/' . $resource_id . '/' . $subresource_type );
			foreach ( $interest_categories['categories'] as $key => $category ) {
				$id                                   = $category['id'];
				$title                                = $category['title'];
				$params['subresource']                = $id;
				$interests                            = $this->parent->mailchimp->load( $resource_type . '/' . $resource_id . '/' . $subresource_type . '/' . $category_id . '/' . $method, $params );
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
			$interests             = $this->parent->mailchimp->load( $resource_type . '/' . $resource_id . '/' . $subresource_type . '/' . $category_id . '/' . $method, $params );
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
