<?php
/*
Plugin Name: MinnPost Form Processor for MailChimp
Plugin URI:
Description: MinnPost runs a form processor plugin that passes data to and from Mailchimp. This plugin handles the user interface for those forms.
Version: 0.0.14
Author: MinnPost
Author URI: https://code.minnpost.com
License: GPL2+
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: minnpost-form-processor-mailchimp
*/

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	return;
}

/**
 * The full path to the main file of this plugin
 *
 * This can later be passed to functions such as
 * plugin_dir_path(), plugins_url() and plugin_basename()
 * to retrieve information about plugin paths
 *
 * @since 0.0.6
 * @var string
 */
define( 'MINNPOST_FORM_PROCESSOR_MAILCHIMP_FILE', __FILE__ );

/**
 * The plugin's current version
 *
 * @since 0.0.6
 * @var string
 */
define( 'MINNPOST_FORM_PROCESSOR_MAILCHIMP_VERSION', '0.0.14' );

// Load the autoloader.
require_once( 'lib/autoloader.php' );

/**
 * Retrieve the instance of the main plugin class
 *
 * @since 2.6.0
 * @return Form_Processor_Mailchimp
 */
function minnpost_form_processor_mailchimp() {
	static $plugin;

	if ( is_null( $plugin ) ) {
		$plugin = new MinnPost_Form_Processor_MailChimp( MINNPOST_FORM_PROCESSOR_MAILCHIMP_VERSION, __FILE__ );
	}

	return $plugin;
}

minnpost_form_processor_mailchimp()->init();
