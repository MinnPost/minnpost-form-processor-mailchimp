<?php
/**
 * Class file for the MinnPost_Form_Processor_MailChimp_Shortcodes class.
 *
 * @file
 */

if ( ! class_exists( 'MinnPost_Form_Processor_MailChimp' ) ) {
	die();
}

/**
 * Create shortcodes functionality
 */
class MinnPost_Form_Processor_MailChimp_Shortcodes {

	public $option_prefix;
	public $parent_option_prefix;
	public $version;
	public $slug;
	public $parent;
	public $get_data;

	/**
	* Constructor which sets up shortcodes
	*/
	public function __construct() {

		$this->option_prefix        = minnpost_form_processor_mailchimp()->option_prefix;
		$this->parent_option_prefix = minnpost_form_processor_mailchimp()->parent_option_prefix;
		$this->version              = minnpost_form_processor_mailchimp()->version;
		$this->slug                 = minnpost_form_processor_mailchimp()->slug;
		$this->parent               = minnpost_form_processor_mailchimp()->parent;
		$this->get_data             = minnpost_form_processor_mailchimp()->get_data;

		add_action( 'plugins_loaded', array( $this, 'add_actions' ) );

	}

	/**
	* Create the action hooks to create the admin page(s)
	*
	*/
	public function add_actions() {
		add_shortcode( 'newsletter_form', array( $this, 'newsletter_form' ) );
	}

	/**
	* Add newsletter embed shortcode
	* This manages the display settings for the newsletter form
	*
	* @param array $attributes
	* @return string $html
	*
	*/
	public function newsletter_form( $attributes, $content = null ) {
		$html    = '';
		$message = '';

		$shortcode = 'newsletter_form';

		$resource_type = $this->get_data->get_resource_type( $shortcode );
		if ( '' === $resource_type ) {
			return $html;
		}

		$resource_id = $this->get_data->get_resource_id( $shortcode );
		if ( '' === $resource_id ) {
			return $html;
		}

		if ( is_admin() ) {
			return $html;
		}
		$form = shortcode_atts(
			array(
				'placement'        => '', // where this is used. fullpage, instory, or sidebar
				'groups_available' => '', // mailchimp groups to make available for the user. default (plugin settings), all, or csv of group names. this should be whatever the form is making available to the user. if there are groups the user is not able to choose in this instance, they should be left out.
				'show_elements'    => '', // title, description. default is based on placement
				'hide_elements'    => '', // title, description. default is based on placement
				'image_url'        => '', // if a local image url is specified, it will be added before the content_before value
				'image_alt'        => '', // if adding an image, alt text should also be added
				'content_before'   => '', // used before form. default is empty.
				'content_after'    => '', // used after form. default is empty.
				'categories'       => '', // categories corresponding to groups. default is empty.
				'confirm_message'  => '', // after submission. default should be in the plugin settings, but it can be customized for specific usage
				'classes'          => '', // classes for css/js to target, if applicable. if there are values here, they will be added to the <form> (or other first markup element) in the template
				'redirect_url'     => $this->get_current_url(), // if not ajax, form will go to this url.
			),
			$attributes
		);

		// these are not shortcode attrs bc they can't be overridden
		$form['user']   = wp_get_current_user();
		$form['action'] = $shortcode;

		if ( 0 !== $form['user'] ) {
			$user_mailchimp_groups        = get_option( $this->option_prefix . $shortcode . '_mc_resource_item_type', '' );
			$user_email                   = $form['user']->user_email;
			$form['user']->mailchimp_info = $this->get_data->get_user_info( $resource_type, $resource_id, $user_email );
			if ( ! is_wp_error( $form['user']->mailchimp_info ) ) {
				$form['user']->groups           = $form['user']->mailchimp_info[ $user_mailchimp_groups ];
				$form['user']->mailchimp_status = $form['user']->mailchimp_info['status'];
			} else {
				// if the user returns no status or a 404 from mailchimp, we need to log it to see what is happening
				error_log( 'error: user from mailchimp is ' . print_r( $form['user']->mailchimp_info, true ) );
			}
			// todo: one thing that would be good is to support multiple email addresses based on a querystring or something, but only if the logged in user was associated with the email address in the querystring
		}

		// groups fields for this shortcode
		$form['group_fields'] = $this->get_data->get_shortcode_groups( $shortcode, $resource_type, $resource_id, $form['groups_available'], $form['placement'], $form['user'] );

		$message = '';
		if ( isset( $_GET['subscribe-message'] ) ) {
			if ( '' === $args['confirm_message'] ) {
				switch ( $_GET['subscribe-message'] ) {
					case 'success-existing':
						$message = __( 'Thanks for updating your email preferences. They will go into effect immediately.', 'minnpost-largo' );
						break;
					case 'success-new':
						$message = __( 'We have added you to the MinnPost mailing list.', 'minnpost-largo' );
						break;
					case 'success-pending':
						$message = __( 'We have added you to the MinnPost mailing list. You will need to click the confirmation link in the email we sent to begin receiving messages.', 'minnpost-largo' );
						break;
					default:
						$message = $args['confirm_message'];
						break;
				}
			} else {
				$message = $args['confirm_message'];
			}
			$message = '<div class="m-form-message m-form-message-info">' . $message . '</div>';
		} else {
			$message = $form['confirm_message'];
		}
		set_query_var( 'message', $message );

		if ( '' !== $form['image_url'] ) {
			$form['image'] = '<figure class="a-shortcode-image"><img src="' . esc_url( $form['image_url'] ) . '"' . $form['image_alt'] . '></figure>';
		} else {
			$form['image'] = '';
		}

		if ( '' !== $form['content_before'] ) {
			$form['content_before'] = wp_kses_post( wpautop( $form['content_before'] ) );
		}

		if ( '' !== $form['content_after'] ) {
			$form['content_after'] = wp_kses_post( wpautop( $form['content_after'] ) );
		}

		if ( '' !== $form['classes'] ) {
			$form['classes'] = ' ' . minnpost_form_processor_mailchimp()->sanitize_html_classes( $form['classes'] );
		}

		// Generate a custom nonce value for the WordPress form submission
		$form['newsletter_nonce'] = wp_create_nonce( 'minnpost_form_processor_mailchimp_nonce' );

		if ( '' !== $form['placement'] ) {
			$html = $this->get_form_html( $form['placement'], 'shortcodes', $form );
		}
		return $html;

	}

	/**
	 * Renders the contents of the given template to a string and returns it.
	 *
	 * @param string $template_name The name of the template to render (without .php)
	 * @param string $location      Folder location for the template (ie shortcodes)
	 * @param array  $form          The PHP variables for the template
	 *
	 * @return string               The contents of the template.
	 */
	public function get_form_html( $template_name, $location = 'shortcodes', $form = null ) {
		if ( ! $form ) {
			$form = array();
		}

		if ( '' !== $location ) {
			$location = $location . '/';
		}

		ob_start();

		do_action( $this->option_prefix . 'before_' . $template_name );

		// allow users to put templates into their theme
		if ( file_exists( get_theme_file_path() . '/' . $this->slug . '-templates/' . $location . $template_name . '.php' ) ) {
			$file = get_theme_file_path() . '/' . $this->slug . '-templates/' . $location . $template_name . '.php';
		} else {
			$file = plugin_dir_path( __FILE__ ) . '../templates/' . $location . $template_name . '.php';
		}

		require( $file );

		do_action( $this->option_prefix . 'after_' . $template_name );

		$html = ob_get_contents();
		ob_end_clean();

		return $html;
	}

	/**
	 * Return the current URL
	 *
	 * @return string $current_url
	 */
	public function get_current_url() {
		if ( is_page() || is_single() ) {
			$current_url = wp_get_canonical_url();
		} else {
			global $wp;
			$current_url = home_url( add_query_arg( array(), $wp->request ) );
		}
		return $current_url;
	}

}
