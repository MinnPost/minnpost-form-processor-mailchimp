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
	public $get_data;

	/**
	* Constructor which sets up shortcodes
	*/
	public function __construct() {

		$this->option_prefix        = minnpost_form_processor_mailchimp()->option_prefix;
		$this->parent_option_prefix = minnpost_form_processor_mailchimp()->parent_option_prefix;
		$this->version              = minnpost_form_processor_mailchimp()->version;
		$this->slug                 = minnpost_form_processor_mailchimp()->slug;
		$this->get_data             = minnpost_form_processor_mailchimp()->get_data;

		add_action( 'plugins_loaded', array( $this, 'add_actions' ) );

	}

	/**
	* Create the action hooks to create the admin page(s)
	*
	*/
	public function add_actions() {
		add_shortcode( 'newsletter_form', array( $this, 'newsletter_form' ) );
		add_shortcode( 'custom-account-preferences-form', array( $this, 'account_preferences_form' ), 10, 2 );
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

		$resource_type = get_option( $this->option_prefix . 'newsletter_form_resource_type', '' );
		if ( '' === $resource_type ) {
			return $html;
		}

		$resource_id = get_option( $this->option_prefix . 'newsletter_form_resource_id', '' );
		if ( '' === $resource_id ) {
			return $html;
		}

		$subresources = array();

		if ( is_admin() ) {
			return $html;
		}
		$form = shortcode_atts(
			array(
				'show_elements'   => '',
				'hide_elements'   => '',
				'confirm_message' => '',
				'redirect_url'    => '',
				'content'         => '',
				'placement'       => '',
				'groups'          => '',
			),
			$attributes
		);

		$form['user'] = wp_get_current_user();

		if ( '' !== $form['subresources'] ) {
			$form['subresources'] = array_map( 'trim', explode( ',', $form['subresources'] ) );
		}

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
			$confirm_message = $form['confirm_message'];
		}

		if ( '' !== $form['content'] ) {
			set_query_var( 'content', wp_kses_post( wpautop( $form['content'] ) ) );
		}

		// Generate a custom nonce value for the WordPress form submission
		$form['newsletter_nonce'] = wp_create_nonce( 'mp_newsletter_form_nonce' );

		if ( '' !== $form['placement'] ) {
			$html = $this->get_form_html( $form['placement'], 'shortcodes', $form );
		}
		return $html;

		if ( '' !== $form['newsletter'] ) {
			if ( 'dc' === $args['newsletter'] ) {
				set_query_var( 'newsletter', 'dc' );
				set_query_var( 'redirect_url', get_current_url() . '#form-newsletter-shortcode-' . $args['newsletter'] );
				set_query_var( 'message', $message );
				ob_start();
				$file = get_template_part( 'inc/forms/newsletter', 'shortcode-dc' );
				$html = ob_get_contents();
				ob_end_clean();
				return $html;
			} elseif ( 'default' === $args['newsletter'] ) {
				set_query_var( 'newsletter', 'default' );
				set_query_var( 'newsletter_nonce', $newsletter_nonce );
				set_query_var( 'redirect_url', get_current_url() );
				set_query_var( 'message', $message );
				ob_start();
				$file = get_template_part( 'inc/forms/newsletter', 'shortcode' );
				$html = ob_get_contents();
				ob_end_clean();
				return $html;
			} elseif ( 'full' === $args['newsletter'] ) {
				set_query_var( 'newsletter', 'full' );
				set_query_var( 'newsletter_nonce', $newsletter_nonce );
				set_query_var( 'redirect_url', get_current_url() );
				set_query_var( 'message', $message );
				ob_start();
				$file = get_template_part( 'inc/forms/newsletter', 'full' );
				$html = ob_get_contents();
				ob_end_clean();
				return $html;
			} elseif ( 'full-dc' === $args['newsletter'] ) {
				set_query_var( 'newsletter', 'full-dc' );
				set_query_var( 'newsletter_nonce', $newsletter_nonce );
				set_query_var( 'redirect_url', get_current_url() );
				set_query_var( 'message', $message );
				ob_start();
				$file = get_template_part( 'inc/forms/newsletter', 'full-dc' );
				$html = ob_get_contents();
				ob_end_clean();
				return $html;
			}
		} else {
			set_query_var( 'newsletter', 'email' );
			set_query_var( 'newsletter_nonce', $newsletter_nonce );
			set_query_var( 'redirect_url', get_current_url() );
			set_query_var( 'message', $message );
			set_query_var( 'confirm_message', $confirm_message );
			ob_start();
			$file = get_template_part( 'inc/forms/newsletter', 'shortcode-email' );
			$html = ob_get_contents();
			ob_end_clean();
			return $html;
		}
	}

	/**
	 * A shortcode for rendering the form used to change a logged in user's preferences
	 *
	 * @param  array   $attributes  Shortcode attributes.
	 * @param  string  $content     The text content for shortcode.
	 *
	 * @return string  The shortcode output
	 */
	public function account_preferences_form( $attributes, $content = null ) {

		if ( ! is_array( $attributes ) ) {
			$attributes = array();
		}

		$user_id = get_query_var( 'users', '' );
		if ( isset( $_GET['user_id'] ) ) {
			$user_id = esc_attr( $_GET['user_id'] );
		} else {
			$user_id = get_current_user_id();
		}

		$can_access = false;
		if ( class_exists( 'User_Account_Management' ) ) {
			$account_management = User_Account_Management::get_instance();
			$can_access         = $account_management->check_user_permissions( $user_id );
		} else {
			return;
		}
		// if we are on the current user, or if this user can edit users
		if ( false === $can_access ) {
			return __( 'You do not have permission to access this page.', 'minnpost-form-processor-mailchimp' );
		}

		// this functionality is mostly from https://pippinsplugins.com/change-password-form-short-code/
		// we should use it for this page as well, unless and until it becomes insufficient

		$attributes['current_url'] = get_current_url();
		$attributes['redirect']    = $attributes['current_url'];

		if ( ! is_user_logged_in() ) {
			return __( 'You are not signed in.', 'minnpost-form-processor-mailchimp' );
		} else {
			//$attributes['login'] = rawurldecode( $_REQUEST['login'] );

			// translators: instructions on top of the form
			$attributes['instructions'] = sprintf( '<p class="a-form-instructions">' . esc_html__( 'If you have set up reading or email preferences, you can update them below.', 'minnpost-form-processor-mailchimp' ) . '</p>' );

			// Error messages
			$errors = array();
			if ( isset( $_REQUEST['errors'] ) ) {
				$error_codes = explode( ',', $_REQUEST['errors'] );

				foreach ( $error_codes as $code ) {
					$errors[] = $account_management->get_error_message( $code );
				}
			}
			$attributes['errors'] = $errors;
			if ( isset( $user_id ) && '' !== $user_id ) {
				$attributes['user'] = get_userdata( $user_id );
			} else {
				$attributes['user'] = wp_get_current_user();
			}
			$attributes['user_meta'] = get_user_meta( $attributes['user']->ID );

			// todo: this should probably be in the database somewhere
			$attributes['reading_topics'] = array(
				'Arts & Culture'         => __( 'Arts & Culture', 'minnpost-form-processor-mailchimp' ),
				'Economy'                => __( 'Economy', 'minnpost-form-processor-mailchimp' ),
				'Education'              => __( 'Education', 'minnpost-form-processor-mailchimp' ),
				'Environment'            => __( 'Environment', 'minnpost-form-processor-mailchimp' ),
				'Greater Minnesota news' => __( 'Greater Minnesota news', 'minnpost-form-processor-mailchimp' ),
				'Health'                 => __( 'Health', 'minnpost-form-processor-mailchimp' ),
				'MinnPost announcements' => __( 'MinnPost announcements', 'minnpost-form-processor-mailchimp' ),
				'Opinion/Commentary'     => __( 'Opinion/Commentary', 'minnpost-form-processor-mailchimp' ),
				'Politics & Policy'      => __( 'Politics & Policy', 'minnpost-form-processor-mailchimp' ),
				'Sports'                 => __( 'Sports', 'minnpost-form-processor-mailchimp' ),
			);

			$attributes['user_reading_topics'] = array();
			if ( isset( $attributes['user_meta']['_reading_topics'] ) ) {
				if ( is_array( maybe_unserialize( $attributes['user_meta']['_reading_topics'][0] ) ) ) {
					$topics = maybe_unserialize( $attributes['user_meta']['_reading_topics'][0] );
					foreach ( $topics as $topic ) {
						$attributes['user_reading_topics'][] = $topic;
					}
				}
			}

			return $account_management->get_form_html( 'account-preferences-form', 'front-end', $attributes );

		}
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

}
