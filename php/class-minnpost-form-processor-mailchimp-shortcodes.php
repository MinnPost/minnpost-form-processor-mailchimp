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

	private $shortcode;

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

		$this->shortcode = 'newsletter_form';

		add_action( 'plugins_loaded', array( $this, 'add_actions' ) );

	}

	/**
	* Create the action hooks to create the admin page(s)
	*
	*/
	public function add_actions() {
		add_shortcode( $this->shortcode, array( $this, 'newsletter_form' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'front_end_scripts_and_styles' ) );
	}

	/**
	* Front end styles. Load the CSS and/or JavaScript for the plugin's settings
	*
	* @return void
	*/
	public function front_end_scripts_and_styles() {
		wp_enqueue_script( $this->slug . '-front-end', plugins_url( 'assets/js/' . $this->slug . '-front-end.min.js', dirname( __FILE__ ) ), array( 'wp-hooks' ), $this->version, true );
		// adwords values
		$google_ads_id               = get_option( $this->option_prefix . $this->shortcode . '_google_ads_id_value', '' );
		$google_ads_id_constant      = get_option( $this->option_prefix . $this->shortcode . '_google_ads_id_constant', '' );
		$google_ads_conversion_label = get_option( $this->option_prefix . $this->shortcode . '_google_ads_conversion_label', '' );
		if ( '' !== $google_ads_id_constant ) {
			$google_ads_id = defined( $google_ads_id_constant ) ? constant( $google_ads_id_constant ) : '';
		}
		// localize
		$params = array(
			'ajaxurl'     => admin_url( 'admin-ajax.php' ),
			'gtag_sendto' => $google_ads_id . '/' . $google_ads_conversion_label,
		);
		wp_localize_script( $this->slug . '-front-end', 'params', $params );
		wp_enqueue_style( $this->slug . '-front-end', plugins_url( 'assets/css/' . $this->slug . '-front-end.min.css', dirname( __FILE__ ) ), array(), $this->version, 'all' );
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

		$shortcode = $this->shortcode;

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
				'placement'                  => '', // where this is used. fullpage, instory, inpopup, useraccount, usersummary, or sidebar
				'groups_available'           => '', // mailchimp groups to make available for the user. default (plugin settings), all, or csv of group names. this should be whatever the form is making available to the user. if there are groups the user is not able to choose in this instance, they should be left out.
				'show_elements'              => '', // title, description. default is based on placement
				'hide_elements'              => '', // title, description. default is based on placement
				'button_text'                => '', // button text for the form. default is 1) whatever is in the plugin settings, 2) if that is blank, Subscribe
				'button_styles'              => '', // button css. will be inlined, if present.
				'image_url'                  => '', // if a local image url is specified, it will be added before the content_before value
				'image_alt'                  => '', // if adding an image, alt text should also be added
				'content_before'             => '', // used before form. default is empty.
				'content_after'              => '', // used after form. default is empty.
				'in_content_label'           => '', // label text on in-content forms. default is empty. this applies to the email field.
				'in_content_label_placement' => '', // if there is a label on the in-content form, where to put it? can be before or after
				'categories'                 => '', // categories corresponding to groups. default is empty.
				'confirm_message'            => '', // after submission. default should be in the plugin settings, but it can be customized for specific usage
				'error_message'              => '', // after submission. default should be in the plugin settings, but it can be customized for specific usage
				'classes'                    => '', // classes for css/js to target, if applicable. if there are values here, they will be added to the <form> (or other first markup element) in the template
				'redirect_url'               => $this->get_current_url(), // if not ajax, form will go to this url.
			),
			$attributes
		);

		// these are not shortcode attrs bc they can't be overridden
		$form['user']   = wp_get_current_user();
		$form['action'] = $shortcode;

		// there is a logged in user. we should check if they're a mailchimp user.
		if ( 0 !== $form['user']->ID ) {
			$user_mailchimp_groups = get_option( $this->option_prefix . $shortcode . '_mc_resource_item_type', '' );
			$user_email            = $form['user']->user_email;

			// filter for changing email
			$user_email = apply_filters( $this->option_prefix . 'set_form_user_email', $user_email, $form['user']->ID );

			// query var for changing email
			$url_email = get_query_var( 'email' );
			if ( '' !== $url_email ) {
				$user_email = $url_email;
			}

			$form['user']->user_email = $user_email;

			// for places where we have to have user data before we submit the form, get it.
			// todo: we could make an optional parameter on the shortcode to set this, as well.
			if ( in_array( $form['placement'], array( 'fullpage', 'useraccount', 'usersummary' ), true ) ) {
				// if the user has already filled out the form, we should reset the cached data
				$reset_user_info = false;
				$message_code    = get_query_var( 'newsletter_message_code' );
				if ( '' !== $message_code ) {
					$reset_user_info = true;
				}
				$form['user']->mailchimp_info = $this->get_data->get_user_info( $shortcode, $resource_type, $resource_id, $user_email, $reset_user_info );

				if ( ! is_wp_error( $form['user']->mailchimp_info ) ) {
					$form['user']->mailchimp_user_id = $form['user']->mailchimp_info['id'];
					$form['user']->groups            = $form['user']->mailchimp_info[ $user_mailchimp_groups ];
					$form['user']->mailchimp_status  = $form['user']->mailchimp_info['status'];
				} else {
					// if the user returns no status or a 404 from mailchimp, we need to log it to see what is happening
					//error_log( 'error: user from mailchimp is ' . print_r( $form['user']->mailchimp_info, true ) );
				}
			}
		}

		// default button text is Subscribe if the form option has no value. Templates can override this as needed, or with an attribute value the individual forms can override it.
		if ( '' === $form['button_text'] ) {
			$form['button_text'] = get_option( $this->option_prefix . $shortcode . '_button_text', __( 'Subscribe', 'minnpost-form-processor-mailchimp' ) );
		}

		// if there is a button style value, write an inline style tag for it
		if ( '' !== $form['button_styles'] ) {
			$form['button_styles'] = ' style="' . esc_attr( $form['button_styles'] ) . '"';
		}

		// allow title and description to be hidden by shortcode attributes
		$form['hide_description'] = false;
		$form['hide_title']       = false;
		if ( '' !== $form['hide_elements'] ) {
			$hide_elements = explode( ',', $form['hide_elements'] );
			if ( in_array( 'description', $hide_elements, true ) ) {
				$form['hide_description'] = true;
			}
			if ( in_array( 'title', $hide_elements, true ) ) {
				$form['hide_title'] = true;
			}
		}

		// allow title and description to be shown by shortcode attributes
		$form['show_description'] = false;
		$form['show_title']       = false;
		if ( '' !== $form['show_elements'] ) {
			$show_elements = explode( ',', $form['show_elements'] );
			if ( in_array( 'description', $hide_elements, true ) ) {
				$form['show_description'] = true;
			}
			if ( in_array( 'title', $hide_elements, true ) ) {
				$form['show_title'] = true;
			}
		}

		// groups fields for this shortcode
		$form['group_fields'] = $this->get_data->get_shortcode_groups( $shortcode, $resource_type, $resource_id, $form['groups_available'], $form['placement'], $form['user'] );

		// default message is empty
		$form['message'] = '';

		// set message for success
		$message_code    = get_query_var( 'newsletter_message_code' );
		$success_message = $this->get_data->get_success_message( $message_code, $form['confirm_message'] );

		// set message for error
		$newsletter_error = get_query_var( 'newsletter_error' );
		$error_message    = $this->get_data->get_error_message( $newsletter_error, $form['error_message'] );

		if ( isset( $_POST['minnpost_form_processor_mailchimp_nonce'] ) && wp_verify_nonce( $_POST['minnpost_form_processor_mailchimp_nonce'], 'minnpost_form_processor_mailchimp_nonce' ) ) {
			if ( '' !== $success_message ) {
				$form['message'] = $success_message;
			}

			if ( '' !== $error_message ) {
				$form['message'] = $error_message;
			}
		}

		// set message for ajax
		if ( '' === $form['message'] ) {
			$form['message'] = '<div class="m-form-message m-form-message-ajax m-form-message-ajax-placeholder"></div>';
		}
		if ( '' !== $form['image_url'] ) {
			$alt_attr = '';
			if ( '' !== $form['image_alt'] ) {
				$alt_attr = ' alt="' . $form['image_alt'] . '"';
			}
			$form['image'] = '<figure class="a-shortcode-image"><img src="' . esc_url( $form['image_url'] ) . '"' . $alt_attr . '></figure>';
		} else {
			$form['image'] = '';
		}

		if ( '' !== $form['content_before'] ) {
			$form['content_before'] = wp_kses_post( apply_filters( 'the_content', urldecode( $form['content_before'] ) ) );
		}

		if ( '' !== $form['content_after'] ) {
			$form['content_after'] = wp_kses_post( apply_filters( 'the_content', urldecode( $form['content_after'] ) ) );
		}

		if ( '' !== $form['in_content_label'] ) {
			$form['in_content_label'] = wp_kses_post( apply_filters( 'the_content', urldecode( $form['in_content_label'] ) ) );
		}

		// for forms that have content, we should set up the classes for good styling.
		if ( '' !== $form['content_before'] || '' !== $form['content_after'] || '' !== $form['image'] ) {
			$form['classes'] .= 'm-form-minnpost-form-processor-mailchimp-has-content';
		}

		if ( '' !== $form['classes'] ) {
			$form['classes'] = ' ' . minnpost_form_processor_mailchimp()->sanitize_html_classes( $form['classes'] );
		}

		// Generate a custom nonce value for the WordPress form submission.
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
