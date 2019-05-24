<?php
/**
 * Class file for the MinnPost_Form_Processor_MailChimp_WP_User_Data class.
 *
 * @file
 */

if ( ! class_exists( 'MinnPost_Form_Processor_MailChimp' ) ) {
	die();
}

/**
 * Get MailChimp Data
 */
class MinnPost_Form_Processor_MailChimp_WP_User_Data {

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
	 * Get the user's MailChimp data from their meta record.
	 *
	 * @param  string  $shortcode
	 * @param  string  $email
	 *
	 * @return  array|bool $user_meta_value
	 */
	public function get_user_meta( $shortcode, $email = '' ) {
		$user_id = get_current_user_id();
		if ( 0 === $user_id ) {
			return false;
		}
		$user_meta_key = get_option( $this->option_prefix . $shortcode . '_user_meta_key', '' );
		if ( '' === $user_meta_key ) {
			return false;
		}
		$user_meta_value = get_user_meta( $user_id, $user_meta_key, true );
		if ( '' === $user_meta_value ) {
			return array();
		}
		$user_meta_value = maybe_unserialize( $user_meta_value );
		if ( '' !== $email ) {
			$user_meta_value = $user_meta_value[ $email ];
		}
		return $user_meta_value;
	}

	/**
	 * Save the user's MailChimp data to their meta record.
	 *
	 * @param  string  $shortcode
	 * @param  array   $params
	 *
	 * @return  array|bool $user_meta
	 */
	public function save_user_meta( $shortcode, $params ) {
		$user_id         = get_current_user_id();
		$user_meta_key   = get_option( $this->option_prefix . $shortcode . '_user_meta_key', '' );
		$user_meta_value = $this->get_user_meta( $shortcode );

		if ( 0 === $user_id || '' === $user_meta_key || ! is_array( $user_meta_value ) ) {
			return false;
		}

		$group_key = get_option( $this->option_prefix . $shortcode . '_mc_resource_item_type', '' );

		$meta_we_care_about = array(
			'email_address',
			'status',
			'merge_fields',
			$group_key,
		);

		$merge_fields_we_care_about = array(
			'FNAME',
			'LNAME',
		);

		if ( is_array( $merge_fields_we_care_about ) ) {
			// MailChimp has a lot of merge fields we don't care about for this purpose
			$params['merge_fields'] = array_intersect_key( $params['merge_fields'], array_flip( $merge_fields_we_care_about ) );
		}

		// store the values from mailchimp that are part of the array of meta we care about
		$params = array_intersect_key( $params, array_flip( $meta_we_care_about ) );

		// make an array for this particular email address and save it to user meta
		$user_meta_value[ $params['email_address'] ] = $params;

		$user_meta_value = serialize( $user_meta_value );
		$user_meta       = update_user_meta( $user_id, $user_meta_key, $user_meta_value );
	}

}
