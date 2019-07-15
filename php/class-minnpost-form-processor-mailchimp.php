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
	 * Sanitize a string of HTML classes
	 *
	 */
	public function sanitize_html_classes( $classes, $sep = ' ' ) {
		$return = '';
		if ( ! is_array( $classes ) ) {
			$classes = explode( $sep, $classes );
		}
		if ( ! empty( $classes ) ) {
			foreach ( $classes as $class ) {
				$return .= sanitize_html_class( $class ) . ' ';
			}
		}
		return $return;
	}

}
