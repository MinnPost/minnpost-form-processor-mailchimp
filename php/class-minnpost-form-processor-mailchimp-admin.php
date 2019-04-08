<?php
/**
 * Class file for the MinnPost_Form_Processor_MailChimp_Admin class.
 *
 * @file
 */

if ( ! class_exists( 'MinnPost_Form_Processor_MailChimp' ) ) {
	die();
}

/**
 * Create default WordPress admin functionality to configure the plugin.
 */
class MinnPost_Form_Processor_MailChimp_Admin {

	public $option_prefix;
	public $parent_option_prefix;
	public $version;
	public $slug;
	public $plugin_file;

	public $parent;

	/**
	* Constructor which sets up admin pages
	*/
	public function __construct() {

		$this->option_prefix        = minnpost_form_processor_mailchimp()->option_prefix;
		$this->parent_option_prefix = minnpost_form_processor_mailchimp()->parent_option_prefix;
		$this->version              = minnpost_form_processor_mailchimp()->version;
		$this->slug                 = minnpost_form_processor_mailchimp()->slug;
		$this->plugin_file          = minnpost_form_processor_mailchimp()->plugin_file;

		$this->parent = minnpost_form_processor_mailchimp()->parent;

		$this->tabs = $this->get_admin_tabs();

		$this->list_member_statuses = array( 'subscribed', 'unsubscribed', 'cleaned', 'pending' );

		$this->add_actions();

	}

	/**
	* Create the action hooks to create the admin page(s)
	*
	*/
	private function add_actions() {
		if ( is_admin() ) {
			add_filter( 'plugin_action_links', array( $this, 'plugin_action_links' ), 10, 2 );
			add_action( 'admin_menu', array( $this, 'create_admin_menu' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts_and_styles' ) );
			add_action( 'admin_init', array( $this, 'admin_settings_form' ) );
		}

	}

	/**
	* Display a Settings link on the main Plugins page
	*
	* @param array $links
	* @param string $file
	* @return array $links
	* These are the links that go with this plugin's entry
	*/
	public function plugin_action_links( $links, $file ) {
		if ( plugin_basename( $this->plugin_file ) === $file ) {
			$settings = '<a href="' . get_admin_url() . 'options-general.php?page=' . $this->slug . '">' . __( 'Settings', 'minnpost-form-processor-mailchimp' ) . '</a>';
			array_unshift( $links, $settings );
		}
		return $links;
	}

	/**
	* Default display for <input> fields
	*
	* @param array $args
	*/
	public function create_admin_menu() {
		add_options_page( __( 'MinnPost MailChimp Settings', 'minnpost-form-processor-mailchimp' ), __( 'MinnPost MailChimp Settings', 'minnpost-form-processor-mailchimp' ), 'manage_options', 'minnpost-form-processor-mailchimp', array( $this, 'show_admin_page' ) );
	}

	/**
	* Admin styles. Load the CSS and/or JavaScript for the plugin's settings
	*
	* @return void
	*/
	public function admin_scripts_and_styles() {
		wp_enqueue_script( $this->slug . '-admin', plugins_url( 'assets/js/admin.min.js', dirname( __FILE__ ) ), array( 'jquery' ), $this->version, true );
		wp_enqueue_style( $this->slug . '-admin', plugins_url( 'assets/css/admin.min.css', dirname( __FILE__ ) ), array(), $this->version, 'all' );
	}

	private function get_admin_tabs() {
		$tabs = array(
			'minnpost_mailchimp_settings' => __( 'MinnPost MailChimp Forms', 'minnpost-form-processor-mailchimp' ),
			//'embed_ads_settings'    => 'Embed Ads Settings',
		); // this creates the tabs for the admin
		$sections = $this->setup_form_sections();
		if ( ! empty( $sections ) ) {
			foreach ( $sections as $key => $form ) {
				$tabs[ $key ] = ucwords( str_replace( '_', ' ', $form ) );
			}
		}

		return $tabs;
	}

	/**
	* Display the admin settings page
	*
	* @return void
	*/
	public function show_admin_page() {
		$get_data = filter_input_array( INPUT_GET, FILTER_SANITIZE_STRING );
		?>
		<div class="wrap">
			<h1><?php _e( get_admin_page_title() , 'minnpost-form-processor-mailchimp' ); ?></h1>

			<?php
			$tabs = $this->tabs;
			$tab  = isset( $get_data['tab'] ) ? sanitize_key( $get_data['tab'] ) : 'minnpost_mailchimp_settings';
			$this->render_tabs( $tabs, $tab );

			switch ( $tab ) {
				case 'minnpost_mailchimp_settings':
					require_once( plugin_dir_path( __FILE__ ) . '/../templates/admin/settings.php' );
					break;
				default:
					require_once( plugin_dir_path( __FILE__ ) . '/../templates/admin/settings.php' );
					break;
			} // End switch().
			?>
		</div>
		<?php
	}

	/**
	* Render tabs for settings pages in admin
	* @param array $tabs
	* @param string $tab
	*/
	private function render_tabs( $tabs, $tab = '' ) {

		$get_data = filter_input_array( INPUT_GET, FILTER_SANITIZE_STRING );

		$current_tab = $tab;
		echo '<h2 class="nav-tab-wrapper">';
		foreach ( $tabs as $tab_key => $tab_caption ) {
			$active = $current_tab === $tab_key ? ' nav-tab-active' : '';
			echo sprintf( '<a class="nav-tab%1$s" href="%2$s">%3$s</a>',
				esc_attr( $active ),
				esc_url( '?page=' . $this->slug . '&tab=' . $tab_key ),
				esc_html( $tab_caption )
			);
		}
		echo '</h2>';

		if ( isset( $get_data['tab'] ) ) {
			$tab = sanitize_key( $get_data['tab'] );
		} else {
			$tab = '';
		}
	}

	/**
	* Register items for the settings api
	* @return void
	*
	*/
	public function admin_settings_form() {

		$get_data = filter_input_array( INPUT_GET, FILTER_SANITIZE_STRING );
		$page     = isset( $get_data['tab'] ) ? sanitize_key( $get_data['tab'] ) : 'minnpost_mailchimp_settings';
		$section  = isset( $get_data['tab'] ) ? sanitize_key( $get_data['tab'] ) : 'minnpost_mailchimp_settings';

		$input_callback_default    = array( $this, 'display_input_field' );
		$textarea_callback_default = array( $this, 'display_textarea' );
		$input_checkboxes_default  = array( $this, 'display_checkboxes' );
		$input_radio_default       = array( $this, 'display_radio' );
		$input_select_default      = array( $this, 'display_select' );
		$link_default              = array( $this, 'display_link' );

		$all_field_callbacks = array(
			'text'       => $input_callback_default,
			'textarea'   => $textarea_callback_default,
			'checkboxes' => $input_checkboxes_default,
			'radio'      => $input_radio_default,
			'select'     => $input_select_default,
			'link'       => $link_default,
		);

		$this->minnpost_mailchimp_settings( 'minnpost_mailchimp_settings', 'minnpost_mailchimp_settings', $all_field_callbacks );
		$this->form_settings( 'form_settings', 'form_settings', $all_field_callbacks );

	}


	/**
	* Fields for the MinnPost MailChimp Settings tab
	* This runs add_settings_section once, as well as add_settings_field and register_setting methods for each option
	*
	* @param string $page
	* @param string $section
	* @param string $input_callback
	*/
	private function minnpost_mailchimp_settings( $page, $section, $callbacks ) {
		$tabs = $this->tabs;
		foreach ( $tabs as $key => $value ) {
			if ( $key === $page ) {
				$title = ucwords( str_replace( '-', ' ', $value ) );
			}
		}
		add_settings_section( $page, $title, null, $page );

		$settings = array(
			'form_shortcodes' => array(
				'title'    => __( 'Newsletter form shortcodes', 'minnpost-form-processor-mailchimp' ),
				'callback' => $callbacks['textarea'],
				'page'     => $page,
				'section'  => $section,
				'args'     => array(
					'type' => 'text',
					'desc' => __( 'Enter shortcodes, one on each line, that are managed by this plugin. Ex: newsletter_form corresponds to [newsletter_form].', 'minnpost-form-processor-mailchimp' ),
				),
			),
		);

		foreach ( $settings as $key => $attributes ) {
			$id       = $this->option_prefix . $key;
			$name     = $this->option_prefix . $key;
			$title    = $attributes['title'];
			$callback = $attributes['callback'];
			$page     = $attributes['page'];
			$section  = $attributes['section'];
			$args     = array_merge(
				$attributes['args'],
				array(
					'title'     => $title,
					'id'        => $id,
					'label_for' => $id,
					'name'      => $name,
				)
			);

			add_settings_field( $id, $title, $callback, $page, $section, $args );
			register_setting( $section, $id );
		}
	}

	private function form_settings( $page, $section, $callbacks ) {
		$form_sections = $this->setup_form_sections();
		$settings      = array();
		if ( ! empty( $form_sections ) ) {
			foreach ( $form_sections as $key => $value ) {
				$section = $key;
				// translators: 1 is the name of the shortcode
				$title = sprintf( 'Shortcode: [%1$s]',
					esc_attr( strtolower( $value ) )
				);

				$page = $section;
				add_settings_section( $section, $title, null, $page );

				$settings[ $section . '_resource_type' ] = array(
					'title'    => __( 'Resource type', 'minnpost-form-processor-mailchimp' ),
					'callback' => $callbacks['select'],
					'page'     => $page,
					'section'  => $section,
					'args'     => array(
						'desc'     => '',
						'constant' => '',
						'type'     => 'select',
						'items'    => $this->get_resource_types(),
					),
				);
				$resource_type                           = get_option( $this->option_prefix . 'newsletter_form_resource_type', '' );
				$settings[ $section . '_resource_id' ]   = array(
					'title'    => __( 'Resource name', 'minnpost-form-processor-mailchimp' ),
					'callback' => $callbacks['select'],
					'page'     => $page,
					'section'  => $section,
					'args'     => array(
						'desc'     => __( 'This is the name (or id, if there is no name) of the MailChimp resource this form can modify.', 'minnpost-form-processor-mailchimp' ),
						'constant' => '',
						'type'     => 'select',
						'items'    => $this->get_resource_ids( $resource_type ),
					),
				);
				$resource_id                             = get_option( $this->option_prefix . 'newsletter_form_resource_id', '' );
				if ( '' === $resource_id ) {
					continue;
				}

				if ( 'lists' === $resource_type ) {
					//list_member_statuses
					$settings[ $section . '_default_member_status' ] = array(
						'title'    => __( 'Default member status', 'minnpost-form-processor-mailchimp' ),
						'callback' => $callbacks['select'],
						'page'     => $page,
						'section'  => $section,
						'args'     => array(
							'desc'     => __( 'When subscribing users to this list, unless otherwise specified they will be added with this status.', 'minnpost-form-processor-mailchimp' ),
							'constant' => '',
							'type'     => 'select',
							'items'    => $this->get_member_statuses(),
						),
					);
				} // End if().

				$mc_resource_items = $this->get_mc_resource_items( $resource_type, $resource_id );

				if ( ! empty( $mc_resource_items ) ) {
					foreach ( $mc_resource_items as $key => $mc_resource_item ) {
						$settings[ $section . '_' . $key . '_title' ]       = array(
							'title'    => __( 'Title', 'minnpost-form-processor-mailchimp' ),
							'callback' => $callbacks['text'],
							'page'     => $page,
							'section'  => $section,
							'class'    => 'minnpost-form-processor-mailchimp-group minnpost-form-processor-mailchimp-group-' . sanitize_title( $mc_resource_item['text'] ),
							'args'     => array(
								'desc'     => __( 'When a form shortcode displays information about this item, it will use this value for the title.', 'minnpost-form-processor-mailchimp' ),
								'constant' => '',
								'type'     => 'text',
							),
						);
						$settings[ $section . '_' . $key . '_description' ] = array(
							'title'    => __( 'Description', 'minnpost-form-processor-mailchimp' ),
							'callback' => $callbacks['textarea'],
							'page'     => $page,
							'section'  => $section,
							'class'    => 'minnpost-form-processor-mailchimp-group minnpost-form-processor-mailchimp-group-' . sanitize_title( $mc_resource_item['text'] ),
							'args'     => array(
								'desc'     => __( 'When a form shortcode displays information about this item, it will use this value for the description.', 'minnpost-form-processor-mailchimp' ),
								'constant' => '',
								'type'     => 'text',
							),
						);
						$settings[ $section . '_' . $key . '_is_default' ]  = array(
							'title'    => __( 'Is default', 'minnpost-form-processor-mailchimp' ),
							'callback' => $callbacks['text'],
							'page'     => $page,
							'section'  => $section,
							'class'    => 'minnpost-form-processor-mailchimp-group minnpost-form-processor-mailchimp-group-' . sanitize_title( $mc_resource_item['text'] ),
							'args'     => array(
								'desc'     => __( 'If this form is submitted without user settings, the user settings will include this item by default.', 'minnpost-form-processor-mailchimp' ),
								'constant' => '',
								'type'     => 'checkbox',
							),
						);

					} // End foreach().
				} // End if().
			} // End foreach().

			foreach ( $settings as $key => $attributes ) {
				$id       = $this->option_prefix . $key;
				$name     = $this->option_prefix . $key;
				$title    = $attributes['title'];
				$callback = $attributes['callback'];
				$page     = $attributes['page'];
				$section  = $attributes['section'];
				$class    = isset( $attributes['class'] ) ? $attributes['class'] : 'minnpost-mailchimp-field ' . $id;
				$args     = array_merge(
					$attributes['args'],
					array(
						'title'     => $title,
						'id'        => $id,
						'label_for' => $id,
						'name'      => $name,
						'class'     => $class,
					)
				);

				// if there is a constant and it is defined, don't run a validate function if there is one
				if ( isset( $attributes['args']['constant'] ) && defined( $attributes['args']['constant'] ) ) {
					$validate = '';
				}
				add_settings_field( $id, $title, $callback, $page, $section, $args );
				register_setting( $section, $id );
			} // End foreach().
		} // End if().
	}

	/**
	* Add the option items individually to the option tabs for benefit pages
	*
	* @param array $sections
	* @param array $names
	* @return $array $sections
	*
	*/
	private function generate_sections( $sections, $names = array() ) {
		if ( ! empty( $names ) ) {
			$names = explode( "\r\n", $names );
			foreach ( $names as $names ) {
				$names       = ltrim( $names, '/' );
				$names_array = explode( '/', $names );
				if ( ! isset( $names_array[1] ) && ! isset( $names_array[2] ) ) {
					$name  = $names_array[0];
					$title = ucwords( str_replace( '-', ' ', $names_array[0] ) );
				} elseif ( isset( $names_array[1] ) && ! isset( $names_array[2] ) ) {
					$name  = $names_array[0] . '-' . $names_array[1];
					$title = ucwords( str_replace( '-', ' ', $names_array[1] ) );
				} elseif ( isset( $names_array[1] ) && isset( $names_array[2] ) ) {
					$name  = $names_array[0] . '-' . $names_array[1] . '-' . $names_array[2];
					$title = ucwords( str_replace( '-', ' ', $names_array[2] ) );
				}
				$sections[ $name ] = $title;
			}
		}
		return $sections;
	}

	/**
	* Set up options tab for each payment page URL in the options
	*
	* @return $array $sections
	*
	*/
	private function setup_form_sections() {
		$sections = array();
		$names    = get_option( $this->option_prefix . 'form_shortcodes', array() );
		if ( ! empty( $names ) ) {
			$sections = $this->generate_sections( $sections, $names );
		}
		return $sections;
	}

	/**
	* Generate an array of resource types
	*
	* @return array $options
	*
	*/
	private function get_resource_types() {
		$options = array();
		if ( ! isset( $_GET['page'] ) || $this->slug !== $_GET['page'] ) {
			return $options;
		}
		$types = get_option( $this->parent_option_prefix . 'resource_types', array() );
		foreach ( $types as $type ) {
			$options[ $type ] = array(
				'text'    => $type,
				'id'      => $type,
				'value'   => $type,
				'desc'    => '',
				'default' => '',
			);
		}
		return $options;
	}

	/**
	* Generate an array of resource ids
	*
	* @param string $resource_type
	* @return array $options
	*
	*/
	private function get_resource_ids( $resource_type = '' ) {
		$options = array();
		if ( ! isset( $_GET['page'] ) || $this->slug !== $_GET['page'] ) {
			return $options;
		}
		if ( '' === $resource_type ) {
			$resource_type = get_option( $this->option_prefix . 'newsletter_form_resource_type', '' );
		}
		$resource_ids = get_option( $this->parent_option_prefix . 'resources_' . $resource_type, array() );
		if ( ! empty( $resource_ids ) && ! empty( $resource_ids[ $resource_type ] ) ) {
			$resource_ids = $resource_ids[ $resource_type ];
		}
		foreach ( $resource_ids as $id ) {
			$resource_name  = $this->parent->mailchimp->get_name( $resource_type, $id );
			$options[ $id ] = array(
				'text'    => isset( $resource_name ) ? $resource_name : $id,
				'id'      => $id,
				'value'   => $id,
				'desc'    => '',
				'default' => '',
			);
		}
		return $options;
	}

	/**
	* Generate an array of member status fields
	*
	* @return array $options
	*
	*/
	private function get_member_statuses() {
		$options = array();
		if ( ! isset( $_GET['page'] ) || $this->slug !== $_GET['page'] ) {
			return $options;
		}
		$statuses = $this->list_member_statuses;
		foreach ( $statuses as $status ) {
			$options[ $status ] = array(
				'text'    => $status,
				'id'      => $status,
				'value'   => $status,
				'desc'    => '',
				'default' => '',
			);
		}
		return $options;
	}

	/**
	* Generate an array of valid MailChimp items available to the given resource. This is used to store settings about each item. Note: here we check the $_GET['page'] value and the global $pagenow value. We don't use get_current_screen() here because it returns an error.
	*
	* @param string $resource_type
	* @param string $resource_id
	* @return array $mc_resource_items
	*
	*/
	private function get_mc_resource_items( $resource_type, $resource_id ) {
		$mc_resource_items = array();
		global $pagenow;
		if ( ( 'options.php' !== $pagenow ) && ( ! isset( $_GET['page'] ) || $this->slug !== $_GET['page'] ) ) {
			return $mc_resource_items;
		}

		$subresource_types = get_option( $this->parent_option_prefix . 'subresource_types_' . $resource_type, array() );
		if ( empty( $subresource_types ) || ! isset( $subresource_types[ $resource_type ] ) ) {
			return $mc_resource_items;
		}
		$subresource_types = $subresource_types[ $resource_type ];
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
				$data_key         = array_search( $item, array_column( $mc_items[ $mailchimp_key ], 'id' ) );
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
	* Default display for <input> fields
	*
	* @param array $args
	*/
	public function display_input_field( $args ) {
		$type    = $args['type'];
		$id      = $args['label_for'];
		$name    = $args['name'];
		$desc    = $args['desc'];
		$checked = '';

		$class = 'regular-text';

		if ( 'checkbox' === $type ) {
			$class = 'checkbox';
		}

		if ( isset( $args['class'] ) ) {
			$class = $args['class'];
		}

		if ( ! isset( $args['constant'] ) || ! defined( $args['constant'] ) ) {
			$value = esc_attr( get_option( $id, '' ) );
			if ( 'checkbox' === $type ) {
				$value = filter_var( get_option( $id, false ), FILTER_VALIDATE_BOOLEAN );
				if ( true === $value ) {
					$checked = 'checked ';
				}
				$value = 1;
			}
			if ( '' === $value && isset( $args['default'] ) && '' !== $args['default'] ) {
				$value = $args['default'];
			}

			echo sprintf(
				'<input type="%1$s" value="%2$s" name="%3$s" id="%4$s" class="%5$s"%6$s>',
				esc_attr( $type ),
				esc_attr( $value ),
				esc_attr( $name ),
				esc_attr( $id ),
				sanitize_html_class( $class, esc_html( ' code' ) ),
				esc_html( $checked )
			);
			if ( '' !== $desc ) {
				echo sprintf( '<p class="description">%1$s</p>',
					esc_html( $desc )
				);
			}
		} else {
			echo sprintf( '<p><code>%1$s</code></p>',
				esc_html__( 'Defined in wp-config.php', 'minnpost-form-processor-mailchimp' )
			);
		}
	}

	/**
	* Default display for <textarea> fields
	*
	* @param array $args
	*/
	public function display_textarea( $args ) {
		$id      = $args['id'];
		$name    = $args['name'];
		$desc    = $args['desc'];
		$checked = '';

		$class = 'regular-text';

		if ( ! isset( $args['constant'] ) || ! defined( $args['constant'] ) ) {
			$value = esc_attr( get_option( $id, '' ) );
			if ( '' === $value && isset( $args['default'] ) && '' !== $args['default'] ) {
				$value = $args['default'];
			}

			echo sprintf( '<textarea name="%1$s" id="%2$s" class="%3$s" rows="10">%4$s</textarea>',
				esc_attr( $name ),
				esc_attr( $id ),
				sanitize_html_class( $class . esc_html( ' code' ) ),
				esc_attr( $value )
			);
			if ( '' !== $desc ) {
				echo sprintf( '<p class="description">%1$s</p>',
					esc_html( $desc )
				);
			}
		} else {
			echo sprintf( '<p><code>%1$s</code></p>',
				esc_html__( 'Defined in wp-config.php', 'minnpost-form-processor-mailchimp' )
			);
		}
	}

	/**
	* Display for multiple checkboxes
	* Above method can handle a single checkbox as it is
	*
	* @param array $args
	*/
	public function display_checkboxes( $args ) {
		$type         = 'checkbox';
		$name         = $args['name'];
		$overall_desc = $args['desc'];
		$options      = get_option( $name, array() );
		foreach ( $args['items'] as $key => $value ) {
			$text        = $value['text'];
			$id          = $value['id'];
			$desc        = $value['desc'];
			$checked     = '';
			$field_value = isset( $value['value'] ) ? esc_attr( $value['value'] ) : esc_attr( $key );

			if ( is_array( $options ) && in_array( (string) $field_value, $options, true ) ) {
				$checked = 'checked';
			} elseif ( is_array( $options ) && empty( $options ) ) {
				if ( isset( $value['default'] ) && true === $value['default'] ) {
					$checked = 'checked';
				}
			}
			echo sprintf( '<div class="checkbox"><label><input type="%1$s" value="%2$s" name="%3$s[]" id="%4$s"%5$s>%6$s</label></div>',
				esc_attr( $type ),
				$field_value,
				esc_attr( $name ),
				esc_attr( $id ),
				esc_html( $checked ),
				esc_html( $text )
			);
			if ( '' !== $desc ) {
				echo sprintf( '<p class="description">%1$s</p>',
					esc_html( $desc )
				);
			}
		}
		if ( '' !== $overall_desc ) {
			echo sprintf( '<p class="description">%1$s</p>',
				esc_html( $overall_desc )
			);
		}
	}

	/**
	* Display for mulitple radio buttons
	*
	* @param array $args
	*/
	public function display_radio( $args ) {
		$type = 'radio';

		$name       = $args['name'];
		$group_desc = $args['desc'];
		$options    = get_option( $name, array() );

		foreach ( $args['items'] as $key => $value ) {
			$text = $value['text'];
			$id   = $value['id'];
			$desc = $value['desc'];
			if ( isset( $value['value'] ) ) {
				$item_value = $value['value'];
			} else {
				$item_value = $key;
			}
			$checked = '';
			if ( is_array( $options ) && in_array( (string) $item_value, $options, true ) ) {
				$checked = 'checked';
			} elseif ( is_array( $options ) && empty( $options ) ) {
				if ( isset( $value['default'] ) && true === $value['default'] ) {
					$checked = 'checked';
				}
			}

			$input_name = $name;

			echo sprintf( '<div class="radio"><label><input type="%1$s" value="%2$s" name="%3$s[]" id="%4$s"%5$s>%6$s</label></div>',
				esc_attr( $type ),
				esc_attr( $item_value ),
				esc_attr( $input_name ),
				esc_attr( $id ),
				esc_html( $checked ),
				esc_html( $text )
			);
			if ( '' !== $desc ) {
				echo sprintf( '<p class="description">%1$s</p>',
					esc_html( $desc )
				);
			}
		}

		if ( '' !== $group_desc ) {
			echo sprintf( '<p class="description">%1$s</p>',
				esc_html( $group_desc )
			);
		}

	}

	/**
	* Display for a dropdown
	*
	* @param array $args
	*/
	public function display_select( $args ) {
		$type = $args['type'];
		$id   = $args['label_for'];
		$name = $args['name'];
		$desc = $args['desc'];
		if ( ! isset( $args['constant'] ) || ! defined( $args['constant'] ) ) {
			$current_value = get_option( $name );

			echo sprintf( '<div class="select"><select id="%1$s" name="%2$s"><option value="">- Select one -</option>',
				esc_attr( $id ),
				esc_attr( $name )
			);

			foreach ( $args['items'] as $key => $value ) {
				$text     = $value['text'];
				$value    = $value['value'];
				$selected = '';
				if ( $key === $current_value || $value === $current_value ) {
					$selected = ' selected';
				}

				echo sprintf( '<option value="%1$s"%2$s>%3$s</option>',
					esc_attr( $value ),
					esc_attr( $selected ),
					esc_html( $text )
				);

			}
			echo '</select>';
			if ( '' !== $desc ) {
				echo sprintf( '<p class="description">%1$s</p>',
					esc_html( $desc )
				);
			}
			echo '</div>';
		} else {
			echo sprintf( '<p><code>%1$s</code></p>',
				esc_html__( 'Defined in wp-config.php', 'minnpost-form-processor-mailchimp' )
			);
		}
	}

	/**
	* Default display for <a href> links
	*
	* @param array $args
	*/
	public function display_link( $args ) {
		$label = $args['label'];
		$desc  = $args['desc'];
		$url   = $args['url'];
		if ( isset( $args['link_class'] ) ) {
			echo sprintf( '<p><a class="%1$s" href="%2$s">%3$s</a></p>',
				esc_attr( $args['link_class'] ),
				esc_url( $url ),
				esc_html( $label )
			);
		} else {
			echo sprintf( '<p><a href="%1$s">%2$s</a></p>',
				esc_url( $url ),
				esc_html( $label )
			);
		}

		if ( '' !== $desc ) {
			echo sprintf( '<p class="description">%1$s</p>',
				esc_html( $desc )
			);
		}

	}

}
