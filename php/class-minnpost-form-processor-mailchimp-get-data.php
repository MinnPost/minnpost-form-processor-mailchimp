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
	public $user_subresource_type;

	/**
	* Constructor which gets data and makes it available
	*/
	public function __construct() {

		$this->option_prefix         = minnpost_form_processor_mailchimp()->option_prefix;
		$this->parent_option_prefix  = minnpost_form_processor_mailchimp()->parent_option_prefix;
		$this->version               = minnpost_form_processor_mailchimp()->version;
		$this->slug                  = minnpost_form_processor_mailchimp()->slug;
		$this->parent                = minnpost_form_processor_mailchimp()->parent;
		$this->user_subresource_type = minnpost_form_processor_mailchimp()->user_subresource_type;
	}

	/**
	* Generate an array of valid MailChimp subresources for this given resource type.
	*
	* @param string $resource_type
	* @return array $subresource_types
	*
	*/
	public function get_mc_subresource_types( $resource_type ) {
		$subresource_types = get_option( $this->parent_option_prefix . 'subresource_types_' . $resource_type, array() );
		if ( empty( $subresource_types ) || ! isset( $subresource_types[ $resource_type ] ) ) {
			return $subresource_types;
		}
		$subresource_types = $subresource_types[ $resource_type ];
		return $subresource_types;
	}

	/**
	* Generate an array of valid MailChimp items available to the given resource. This is used to store settings about each item. Note: here we check the $_GET['page'] value and the global $pagenow value. We don't use get_current_screen() here because it returns an error.
	*
	* @param string $resource_type
	* @param string $resource_id
	* @return array $mc_resource_items
	*
	*/
	public function get_mc_resource_items( $resource_type, $resource_id ) {
		$mc_resource_items = array();
		$subresource_types = $this->get_mc_subresource_types( $resource_type );
		if ( empty( $subresource_types ) ) {
			return $mc_resource_items;
		}
		foreach ( $subresource_types as $subresource_type ) {
			$items = get_option( $this->parent_option_prefix . 'subresources_' . $resource_id . '_' . $subresource_type, array() );
			if ( empty( $items ) || ! isset( $items[ $resource_type ][ $resource_id ][ $subresource_type ] ) ) {
				return $mc_resource_items;
			}
			$subresources = $items[ $resource_type ][ $resource_id ][ $subresource_type ];
			$methods      = get_option( $this->parent_option_prefix . 'subresource_methods', array() );
			if ( empty( $methods ) || empty( $subresources ) ) {
				return $mc_resource_items;
			}
			$methods = $methods[ $resource_type ][ $resource_id ][ $subresource_type ];
			foreach ( $subresources as $subresource ) {
				foreach ( $methods as $method ) {
					$method_items = $this->get_all_items( $resource_type, $resource_id, $subresource_type, $subresource, $method );
					foreach ( $method_items as $method_item ) {
						$mc_resource_items[ $subresource_type . '_' . $subresource . '_' . $method . '_' . $method_item['id'] ] = $method_item;
					} // End foreach().
				} // End foreach().
			} // End foreach().
		} // End foreach().
		return $mc_resource_items;
	}

	/**
	* Generate an array of MailChimp items that can be acted upon. This doesn't need to check for the current $_GET['page'] because it is not called automatically.
	*
	* @param string $resource_type
	* @param string $resource_id
	* @param string $subresource_type
	* @param string $subresource
	* @param string $method
	* @return array $options
	*
	*/
	private function get_all_items( $resource_type, $resource_id, $subresource_type, $subresource, $method ) {
		$options   = array();
		$all_items = get_option( $this->parent_option_prefix . 'items_' . $resource_id . '_' . $subresource_type . '_' . $subresource . '_' . $method, array() );
		if ( empty( $all_items ) ) {
			return $options;
		}
		$all_items     = $all_items[ $resource_type ][ $resource_id ][ $subresource_type ];
		$mc_items      = $this->parent->mailchimp->load( $resource_type . '/' . $resource_id . '/' . $subresource_type . '/' . $subresource . '/' . $method );
		$mailchimp_key = $method;
		if ( ! isset( $mc_items[ $mailchimp_key ] ) ) {
			return $options;
		}

		if ( ! empty( $all_items ) ) {
			foreach ( $all_items as $item ) {
				$data_key         = array_search( $item, array_column( $mc_items[ $mailchimp_key ], 'id' ), true );
				$mc_data          = $mc_items[ $mailchimp_key ][ $data_key ];
				$options[ $item ] = array(
					'text'    => $mc_data['name'],
					'id'      => $item,
					'value'   => $item,
					'desc'    => '',
					'default' => '',
				);
			}
		}
		return $options;
	}

	/**
	* Get a user's information from MailChimp
	*
	* @param string $resource_id
	* @param string $email
	* @param bool $reset
	* @return array $user
	*/
	public function get_user_info( $resource_type, $resource_id, $email, $reset = false ) {
		// email needs to be lowercase before being hashed
		// see: https://developer.mailchimp.com/documentation/mailchimp/guides/manage-subscribers-with-the-mailchimp-api/
		/*
		In previous versions of the API, we exposed internal database IDs eid and leid for emails and list/email combinations. In API 3.0, we no longer use or expose either of these IDs. Instead, we identify your subscribers by the MD5 hash of the lowercase version of their email address so you can easily predict the API URL of a subscriber’s data.
		*/
		if ( is_email( $email ) ) {
			$email = md5( strtolower( $email ) );
		}
		$user = $this->parent->mailchimp->load( $resource_type . '/' . $resource_id . '/' . $this->user_subresource_type . '/' . $email, array(), $reset );

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

	/**
	* Get the resource type for a shortcode
	*
	* @param string $shortcode
	* @return string $resource_type
	*
	*/
	public function get_resource_type( $shortcode = '' ) {
		$resource_type = get_option( $this->option_prefix . $shortcode . '_resource_type', '' );
		return $resource_type;
	}

	/**
	* Get the resource ID for a shortcode
	*
	* @param string $shortcode
	* @return string $resource_id
	*
	*/
	public function get_resource_id( $shortcode = '' ) {
		$resource_id = get_option( $this->option_prefix . $shortcode . '_resource_id', '' );
		return $resource_id;
	}

}
