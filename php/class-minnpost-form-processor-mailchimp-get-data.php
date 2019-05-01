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
	* Get available groups based on the shortcode settings
	*
	* @param string $shortcode
	* @param string $resource_type
	* @param string $resource_id
	* @param array $groups_available
	* @param string $placement
	* @return array $groups_available
	*/
	public function get_shortcode_groups( $shortcode, $resource_type, $resource_id, $groups_available, $placement = '', $user = 0 ) {
		$shortcode_resource_items = array();
		$default_resource_items   = get_option( $this->option_prefix . $shortcode . '_default_mc_resource_items', array() );
		$reserved_group_values    = array(
			'all',
			'default',
		);
		// the shortcode has group names in it, in a csv format
		if ( ! empty( $groups_available ) && ! in_array( $groups_available, $reserved_group_values, true ) ) {
			$groups_available = array_map( 'trim', explode( ',', $groups_available ) );

			$group_ids         = array();
			$mc_resource_items = $this->get_mc_resource_items( $resource_type, $resource_id );
			foreach ( $mc_resource_items as $key => $value ) {
				// check for the name of this group to see if it is specified in the shortcoee
				$option = get_option( $this->option_prefix . $shortcode . '_' . $key . '_name_in_shortcode', '' );
				if ( '' !== $option ) {
					$group_ids[ $option ] = $value['id'];
				}
			}
			foreach ( $groups_available as $group_name ) {
				$key = array_search( $group_name, array_keys( $group_ids ), true );
				if ( false !== $key ) {
					$shortcode_resource_items[] = $group_ids[ $group_name ];
				}
			}
		} elseif ( 'all' === $groups_available ) {
			// shortcode specifies it wants all available groups
			$group_ids         = array();
			$mc_resource_items = $this->get_mc_resource_items( $resource_type, $resource_id );
			foreach ( $mc_resource_items as $key => $value ) {
				$shortcode_resource_items[] = $value['id'];
			}
		} else {
			// the shortcode does not have group names in it, or it specifies to use the defaults. use the defaults.
			$shortcode_resource_items = $default_resource_items;
		}

		if ( ! empty( $shortcode_resource_items ) ) {
			$shortcode_groups = array();
			foreach ( $shortcode_resource_items as $id ) {
				$shortcode_groups[ $id ] = array(
					'id'      => $id,
					'default' => false,
				);
				if ( in_array( $id, $default_resource_items, true ) ) {
					$shortcode_groups[ $id ]['default'] = true;
				}
			}
			$groups_available = $shortcode_groups;
		}

		// groups available based on where the shortcode is being placed. this includes user information. need to remember to set the default checked ones somewhere, too.
		$groups_available = $this->get_placement_groups( $shortcode, $resource_type, $resource_id, $groups_available, $placement, $user );

		return $groups_available;

	}

	/**
	* Get available groups based on the placement and user settings
	*
	* @param string $shortcode
	* @param string $resource_type
	* @param string $resource_id
	* @param array $groups_available
	* @param string $placement
	* @param object $user
	* @return array $groups_available
	*/
	public function get_placement_groups( $shortcode, $resource_type, $resource_id, $groups_available, $placement = '', $user = 0 ) {

		// placement names/groups. todo: it'd probably be nice to put this into the plugin settings
		$placements_user_groups_available = array(
			'usersummary',
			'instory',
		);

		$placements_uncategorized = array(
			'instory',
			'widget',
		);

		$placements_categorized = array(
			'usersummary',
			'useraccount',
			'fullpage',
		);

		// get group info into a useful array
		$groups_available = $this->setup_group_categorization( $shortcode, $resource_type, $resource_id, $groups_available );

		// todo: figure out how to account for this stuff in the plugin options

		// set the defaults based on user groups
		if ( ! empty( $user->groups ) ) {
			$user_groups = array_keys( $user->groups, true, true );
			foreach ( $groups_available as $key => $group ) {
				if ( in_array( $group['id'], $user_groups, true ) ) {
					$groups_available[ $key ]['default'] = true;
				}
			}
		}

		// placement locations that depend on user's existing settings for the available groups:
		if ( in_array( $placement, $placements_user_groups_available, true ) ) {
			if ( ! empty( $user_groups ) ) {
				$group_keys = array_intersect( $user_groups, array_column( $groups_available, 'id' ) );
				if ( in_array( $placement, $placements_uncategorized, true ) ) {
					$groups_available = $group_keys;
				} else {
					$formatted_groups_available = array();
					foreach ( $groups_available as $key => $item ) {
						if ( in_array( $item['id'], $group_keys, true ) ) {
							$formatted_groups_available[ $key ] = $item;
						}
					}
					$groups_available = $formatted_groups_available;
				}
			}
		}

		// placement locations that need mailchimp categorization info:
		if ( in_array( $placement, $placements_categorized, true ) ) {
			// set group layout attributes
			$groups_available = $this->setup_categorized_layout_attributes( $shortcode, $resource_type, $resource_id, $groups_available );
		} elseif ( in_array( $placement, $placements_uncategorized, true ) ) {
			$groups_available = array_column( $groups_available, 'id' );
		}

		return $groups_available;

	}

	/**
	* Setup MailChimp categorization attributes for each group to be in a form
	*
	* @param string $shortcode
	* @param string $resource_type
	* @param string $resource_id
	* @param array $groups_available
	* @return array $group_data
	*/
	public function setup_group_categorization( $shortcode, $resource_type, $resource_id, $groups_available ) {

		$group_data        = array();
		$mc_resource_items = $this->get_mc_resource_items( $resource_type, $resource_id );
		foreach ( $groups_available as $key => $group ) {
			$group_id          = $group['id'];
			$resource_item_key = array_search(
				$group_id,
				array_combine(
					array_keys( $mc_resource_items ),
					array_column( $mc_resource_items, 'id' )
				),
				true
			);
			if ( false !== $resource_item_key ) {
				$mc_resource_attributes = explode( '_', $resource_item_key );
				$subresources           = get_option( $this->parent_option_prefix . 'subresources_' . $resource_id . '_' . $mc_resource_attributes[0], array() );
				if ( ! empty( $subresources ) && isset( $subresources[ $resource_type ] ) && isset( $subresources[ $resource_type ][ $resource_id ] ) ) {
					$subresources_info = $subresources[ $resource_type ][ $resource_id ];
				}
			}

			$group_type   = get_option( $this->option_prefix . $shortcode . '_mc_resource_item_type', '' );
			$subresources = array();
			foreach ( $subresources_info as $type => $ids ) {
				foreach ( $ids as $id ) {
					$groups = get_option( $this->parent_option_prefix . 'items_' . $resource_id . '_' . $type . '_' . $id . '_' . $group_type, array() );
					if ( ! empty( $groups ) ) {
						$groups = $groups[ $resource_type ][ $resource_id ][ $type ];
						if ( in_array( $group_id, $groups, true ) ) {
							$subresources[] = array(
								'type'        => $type,
								'id'          => $id,
								'name'        => get_option( $this->option_prefix . $shortcode . '_' . $id . '_title', '' ),
								'description' => get_option( $this->option_prefix . $shortcode . '_' . $id . '_description', '' ),
							);
						}
					}
				}
			}

			$group_data[ $key ] = array(
				'type'         => $group_type,
				'id'           => $group_id,
				'default'      => $group['default'],
				'subresources' => $subresources,
			);
		} // end foreach

		return $group_data;

	}

	/**
	* Get layout related attributes for the groups when they are categorized
	*
	* @param string $shortcode
	* @param string $resource_type
	* @param string $resource_id
	* @param array $groups
	* @return array $grouped_groups
	*/
	private function setup_categorized_layout_attributes( $shortcode, $resource_type, $resource_id, $groups ) {
		$grouped_groups = array();
		// setup user groups
		foreach ( $groups as $key => $item ) {
			foreach ( $item['subresources'] as $subresource ) {
				$grouped_groups[ $subresource['id'] ]['type']     = $subresource['type'];
				$grouped_groups[ $subresource['id'] ]['id']       = $subresource['id'];
				$grouped_groups[ $subresource['id'] ]['contains'] = $item['type'];
				if ( '' === $subresource['name'] ) {
					$grouped_groups[ $subresource['id'] ]['name'] = $this->parent->mailchimp->get_name( $resource_type, $resource_id, $subresource['type'], $subresource['id'] );
				} else {
					$grouped_groups[ $subresource['id'] ]['name'] = $subresource['name'];
				}
				unset( $item['subresources'] );
				$grouped_groups[ $subresource['id'] ][ $item['type'] ][] = array(
					'id'          => $item['id'],
					'name'        => get_option( $this->option_prefix . $shortcode . '_' . $subresource['type'] . '_' . $subresource['id'] . '_' . $item['type'] . '_' . $item['id'] . '_title', '' ),
					'default'     => $item['default'],
					'description' => get_option( $this->option_prefix . $shortcode . '_' . $subresource['type'] . '_' . $subresource['id'] . '_' . $item['type'] . '_' . $item['id'] . '_description', '' ),
				);
			}
		}
		return $grouped_groups;
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
