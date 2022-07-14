<?php
/** @noinspection PhpUnused */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Invalid request.' );
}

/**
 * Tauri Settings API wrapper class.
 *
 * Based on https://github.com/tareq1988/wordpress-settings-api-class
 *
 */
if ( ! class_exists( 'MG_UPC_Settings_API' ) ) :
	class MG_UPC_Settings_API {

		/**
		 * settings sections array
		 *
		 * @var array
		 */
		protected $settings_sections = array();

		/**
		 * Settings fields array
		 *
		 * @var array
		 */
		protected $settings_fields = array();

		/**
		 * if has any color picker
		 *
		 * @var boolean
		 */
		protected $has_color_picker = false;

		/**
		 * if has any media field
		 *
		 * @var boolean
		 */
		protected $has_media_field = false;

		/**
		 * if has any sortable array field
		 *
		 * @var boolean
		 */
		protected $has_sortable = false;

		/**
		 * if has any media field
		 *
		 * @var boolean
		 */
		protected $has_date = false;

		/**
		 * @var bool|false|string
		 */
		public $page_ref;


		public function __construct() {
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ), 10, 1 );
		}

		/**
		 * Enqueue scripts and styles
		 *
		 * @param $hook
		 */
		public function admin_enqueue_scripts( $hook ) {
			if ( $hook !== $this->page_ref ) {
				return;
			}

			$plugin_dir_url = plugin_dir_url( MG_UPC_PLUGIN_FILE );

			if ( true === $this->has_color_picker ) {
				wp_enqueue_style( 'wp-color-picker' );
				wp_enqueue_script( 'wp-color-picker' );
			}
			if ( true === $this->has_sortable ) {
				wp_enqueue_script( 'jquery-ui-sortable' );
				wp_enqueue_script( 'jquery-ui-droppable' );
			}
			if ( true === $this->has_media_field ) {
				wp_enqueue_media();
			}
			if ( true === $this->has_date ) {
				wp_enqueue_script( 'jquery-ui-datepicker' );

				wp_enqueue_style(
					'mg-upc-jquery-ui',
					$plugin_dir_url . 'css/admin/jquery-ui/jquery-ui.css',
					false,
					'1.0.3',
					false
				);
			}

			wp_enqueue_style(
				'mg-upc-settings-css',
				$plugin_dir_url . 'css/settings.css',
				false,
				'1.0.3',
				false
			);

			wp_enqueue_script( 'jquery' );

			wp_enqueue_script(
				'mg-upc-settings.js',
				$plugin_dir_url . 'javascript/admin/settings.js',
				array( 'jquery' ),
				'1.0.3',
				false
			);

			do_action( 'mg_upc_settings_enqueue' );

		}

		/**
		 * Set settings sections
		 *
		 * @param array $sections setting sections array
		 *
		 * @return MG_UPC_Settings_API
		 */
		public function set_sections( $sections ) {
			$this->settings_sections = $sections;
			foreach ( $this->settings_sections as $idx => $section ) {
				// For save as array of sections (field as key)
				if ( ! isset( $section['as_array'] ) ) {
					$section['as_array'] = true;
				}
				if ( is_numeric( $idx ) ) {
					$this->settings_sections[ $section['id'] ] = $section;
					unset( $this->settings_sections[ $idx ] );
				}
			}

			return $this;
		}

		/**
		 * Add a single section
		 *
		 * @param array $section
		 *
		 * @return MG_UPC_Settings_API
		 */
		public function add_section( $section ) {
			if ( ! isset( $section['as_array'] ) ) {
				$section['as_array'] = true;
			}
			$this->settings_sections[ $section['id'] ] = $section;

			return $this;
		}

		/**
		 * Set settings fields
		 *
		 * @param array $fields settings fields array
		 *
		 * @return MG_UPC_Settings_API
		 */
		public function set_fields( $fields ) {
			$this->settings_fields = $fields;

			foreach ( $this->settings_fields as $section => $field ) {
				foreach ( $field as $option ) {
					$this->set_flags_from_field( $option );
				}
			}

			return $this;
		}

		public function add_field( $section, $field ) {
			$defaults = array(
				'name'  => '',
				'label' => '',
				'desc'  => '',
				'type'  => 'text',
			);

			$arg                                 = wp_parse_args( $field, $defaults );
			$this->settings_fields[ $section ][] = $arg;
			$this->set_flags_from_field( $arg );

			return $this;
		}

		private function set_flags_from_field( $arg ) {
			if ( 'color' === $arg['type'] ) {
				$this->has_color_picker = true;
			} elseif ( 'file' === $arg['type'] ) {
				$this->has_media_field = true;
			} elseif ( 'date' === $arg['type'] ) {
				$this->has_date = true;
			} elseif ( 'datetime' === $arg['type'] ) {
				$this->has_date = true;
			} elseif ( 'array' === $arg['type'] ) {
				if ( isset( $arg['sortable'] ) && true === $arg['sortable'] ) {
					$this->has_sortable = true;
				}
			}
		}

		/**
		 * Initialize and registers the settings sections and fields to WordPress
		 *
		 * Usually this should be called at `admin_init` hook.
		 *
		 * This function gets the initiated settings sections and fields. Then
		 * registers them to WordPress and ready for use.
		 */
		public function admin_init() {
			//register settings sections
			foreach ( $this->settings_sections as $section_id => $section ) {
				// For save as array of sections (field as key)
				if ( true === $section['as_array'] ) {
					if ( false === get_option( $section['id'] ) ) {
						add_option( $section['id'] );
					}
				}
				if ( isset( $section['desc'] ) && ! empty( $section['desc'] ) ) {
					$callback = function () use ( $section ) {
						echo '<div class="inside">';
						echo wp_kses( $section['desc'], $this->get_allowed_html() );
						echo '</div>';
					};
				} elseif ( isset( $section['callback'] ) ) {
					$callback = $section['callback'];
				} else {
					$callback = null;
				}

				add_settings_section( $section['id'], $section['title'], $callback, $section['id'] );
			}

			//register settings fields
			foreach ( $this->settings_fields as $section => $section_fields ) {
				foreach ( $section_fields as $option ) {
					$this->setup_option( $section, $option );
				}
			}

			// creates our settings in the options table for as_array section
			foreach ( $this->settings_sections as $section ) {
				if ( true === $section['as_array'] ) {
					$wp_option_name = $section['id'];
					$args           = array(
						'sanitize_callback' => function ( $option_value ) use ( $wp_option_name ) {
							return $this->sanitize_options_section_as_array( $option_value, $wp_option_name );
						},
					);
					register_setting( $section['id'], $section['id'], $args );
				}
			}

		}

		/**
		 * Add setting field and if is real option register setting
		 *
		 * @param string $section Section name
		 * @param array $option Option associative array
		 *
		 * @return array $args parameter to render callback function
		 */
		protected function setup_option( $section, $option ) {

			$name        = $option['name'];
			$type        = isset( $option['type'] ) ? $option['type'] : 'text';
			$label       = isset( $option['label'] ) ? $option['label'] : '';
			$callback    = isset( $option['callback'] ) ? $option['callback'] : array( $this, 'callback_' . $type );
			$option_name = $name;

			if ( true === $this->settings_sections[ $section ]['as_array'] ) {
				$option_name = sprintf( '%1$s[%2$s]', $section, $name );
			}

			$args = $this->get_field_render_args( $section, $option, $label, $option_name, $name, $type );

			if ( true !== $this->settings_sections[ $section ]['as_array'] ) { //Real wp option
				if ( false === get_option( $option_name ) ) {
					add_option( $option_name, $args['std'] );
				}
			}

			add_settings_field( $option_name, $label, $callback, $section, $section, $args );

			if ( true !== $this->settings_sections[ $section ]['as_array'] ) {
				$setting_args = array();
				if ( 'array' === $type ) {
					$setting_args['sanitize_callback'] = function ( $option_value ) use ( $section, $option_name ) {
						return $this->sanitize_options_array_type( $option_value, $section, $option_name );
					};
				} else {
					$setting_args['sanitize_callback'] = function ( $option_value ) use ( $section, $option_name ) {
						return $this->sanitize_options( $option_value, $section, $option_name );
					};
				}

				register_setting( $this->settings_sections[ $section ]['id'], $name, $setting_args );
			}

			return $args;
		}

		/**
		 * Return the array with values that needs for render field
		 *
		 * @param string $section_id The section name
		 * @param array $option The options for the field
		 * @param string $label The label to show for field
		 * @param string $option_input_name The name for the input html tag
		 * @param string $option_id The option id (option_name)
		 * @param string $type Field type [text|date|datetime|number|color|password|wysiwyg|multicheck|selectbox|radio|checkbox|textarea|html|file|array]
		 * @param string $value The value of field, not call to get_option on render
		 *
		 * @return array                        The array with values that needs for render field
		 */
		public function get_field_render_args( $section_id, $option, $label, $option_input_name, $option_id, $type, $value = null ) {

			if ( false === $label ) {
				$label = isset( $option['label'] ) ? $option['label'] : '';
			}
			if ( false === $type ) {
				$type = isset( $option['type'] ) ? $option['type'] : 'text';
			}

			$args = array(
				'id'                => $option_id,
				'class'             => isset( $option['class'] ) ? $option['class'] : '',
				'label_for'         => $option_input_name,
				'desc'              => isset( $option['desc'] ) ? $option['desc'] : '',
				'name'              => $label,
				'section'           => $section_id,
				'size'              => isset( $option['size'] ) ? $option['size'] : null,
				'options'           => isset( $option['options'] ) ? $option['options'] : '',
				'std'               => isset( $option['default'] ) ? $option['default'] : '',
				'sanitize_callback' => isset( $option['sanitize_callback'] ) ? $option['sanitize_callback'] : '',
				'type'              => $type,
				'placeholder'       => isset( $option['placeholder'] ) ? $option['placeholder'] : '',
				'min'               => isset( $option['min'] ) ? $option['min'] : '',
				'max'               => isset( $option['max'] ) ? $option['max'] : '',
				'step'              => isset( $option['step'] ) ? $option['step'] : '',
				'option_name'       => $option_input_name,
				'readonly'          => isset( $option['can_edit'] ) ? ! $option['can_edit'] : false, //only supports for text, number and checkbox
			);

			if ( 'array' === $type && isset( $option['item_fields'] ) ) {
				$args['item_fields'] = $option['item_fields'];
				if ( isset( $option['item_title'] ) ) {
					$args['item_title'] = $option['item_title'];
				}
				if ( isset( $option['new_item_title'] ) ) {
					$args['new_item_title'] = $option['new_item_title'];
				}
				if ( isset( $option['remove_item_text'] ) ) {
					$args['remove_item_text'] = $option['remove_item_text'];
				}
				$args['sortable'] = false;
				if ( isset( $option['sortable'] ) && true === $option['sortable'] ) {
					$args['sortable'] = true;
				}
				$args['can_remove'] = true;
				if ( isset( $option['can_remove'] ) && false === $option['can_remove'] ) {
					$args['can_remove'] = false;
				}
			}

			if ( null !== $value ) {
				$args['value'] = $value;
			}

			return apply_filters(
				'mg_upc_settings_field_render_args',
				$args,
				$section_id,
				$option,
				$label,
				$option_input_name,
				$option_id,
				$type,
				$value
			);
		}

		public function get_allowed_html() {

			static $default_attribs = array(
				'id'             => array(),
				'class'          => array(),
				'title'          => array(),
				'style'          => array(),
				'data'           => array(),
				'data-mce-id'    => array(),
				'data-mce-style' => array(),
				'data-mce-bogus' => array(),
			);

			return array(
				'div'        => $default_attribs,
				'span'       => $default_attribs,
				'p'          => $default_attribs,
				'a'          => array_merge(
					$default_attribs,
					array(
						'href'   => array(),
						'target' => array( '_blank', '_top' ),
					)
				),
				'img'        => array_merge(
					$default_attribs,
					array(
						'src' => array(),
						'alt' => array(),
					)
				),
				'u'          => $default_attribs,
				'i'          => $default_attribs,
				'q'          => $default_attribs,
				'b'          => $default_attribs,
				'ul'         => $default_attribs,
				'ol'         => $default_attribs,
				'li'         => $default_attribs,
				'br'         => $default_attribs,
				'hr'         => $default_attribs,
				'strong'     => $default_attribs,
				'blockquote' => $default_attribs,
				'del'        => $default_attribs,
				'strike'     => $default_attribs,
				'em'         => $default_attribs,
				'code'       => $default_attribs,
				'small'      => $default_attribs,
			);
		}

		/**
		 * Get field description for display
		 *
		 * @param array $args settings field args
		 *
		 */
		public function print_field_description( $args ) {
			if ( ! empty( $args['desc'] ) ) {
				printf(
					'<p class="description">%s</p>',
					wp_kses( $args['desc'], $this->get_allowed_html() )
				);
			}
		}

		/**
		 * Displays an array field for a settings field
		 *
		 * @param array $args settings field args
		 */
		public function callback_array( $args ) {

			$this->print_field_description( $args );

			$array_value = $this->get_option( $args['id'], $args['section'], $args['std'] );
			if ( empty( $array_value ) ) {
				$array_value = array();
			}

			$option_input_name_base = $args['option_name'];

			if ( true === $args['sortable'] ) {
				echo "<div class='mg-upc-sortable-items-container'>";
			}

			$max = 0;
			foreach ( $array_value as $item_index => $item ) {

				$item_title = $item_index . '.';
				if ( isset( $args['item_title'] ) ) {
					$item_title = vsprintf( $args['item_title'], array_merge( array( $item_index ), array_values( $item ) ) );
				}

				$option_input_name_key = $option_input_name_base . '[' . $item_index . ']';
				echo '<div class="mg-upc-array-item" id="' . esc_attr( $option_input_name_key ) . '[div]">';
				if ( true === $args['sortable'] ) {
					echo '<span class="dashicons dashicons-sort mg-upc-sort-handle"> </span>';
				}
				echo '<h3>';
				echo esc_html( $item_title );
				if ( true === apply_filters( 'mg_upc_settings_array_can_remove_item', $args['can_remove'], $args, $item ) ) {
					$remove_item_text = __( 'Remove Item', 'user-post-collections' );
					if ( ! empty( $args['remove_item_text'] ) ) {
						$remove_item_text = $args['remove_item_text'];
					}
					printf(
						" <a href='javascript:void(0)' class='button mg-upc-array-item-remove' data-item-remove='%s'>",
						esc_attr( $option_input_name_key )
					);
					echo "<span class='dashicons-before dashicons-trash'>";
					echo wp_kses( $remove_item_text, $this->get_allowed_html() );
					echo '</span>';
					echo '</a>';
				}
				echo '</h3>';
				do_action( 'mg_upc_settings_array_pre_item', $array_value, $item_index, $args );
				foreach ( $args['item_fields'] as $item_option ) {
					// set input name as array item of option.
					$option_input_name = $option_input_name_key . '[' . $item_option['name'] . ']';

					$item_option_args = $this->get_field_render_args(
						$args['section'],
						$item_option,
						false,
						$option_input_name,
						$args['id'],
						false,
						$item[ $item_option['name'] ]
					);

					$type            = isset( $item_option['type'] ) ? $item_option['type'] : 'text';
					$callback_render = isset( $item_option['callback'] ) ? $item_option['callback'] : array(
						$this,
						'callback_' . $type,
					);

					if ( ! empty( $item_option['label'] ) ) {
						echo '<label ';
						if ( $item_option_args['readonly'] ) {
							echo 'readonly';
						} else {
							echo 'for="' . esc_attr( $option_input_name ) . '"';
						}
						echo '>' . esc_html( $item_option['label'] ) . ': </label>';
					}

					if ( ! is_callable( $callback_render ) ) {
						$callback_render = array( $this, 'callback_text' );
					}
					call_user_func( $callback_render, $item_option_args );

					if ( ! empty( $item_option['label'] ) ) {
						echo '<br>';
					}
				}
				do_action( 'mg_upc_settings_array_end_item', $array_value, $item_index, $args );

				if ( $max < $item_index ) {
					$max = $item_index;
				}

				echo '<hr><br></div>';
			}

			if ( true === $args['sortable'] ) {
				echo '</div>';
			}

			if ( true === $args['can_remove'] ) {

				$option_input_name_key = $option_input_name_base . '[' . ( $max + 1 ) . ']';
				$pre_fake_name         = 'rem:-:me_:';

				$add_new_item = 'Add new Item';
				if ( ! empty( $args['new_item_title'] ) ) {
					$add_new_item = $args['new_item_title'];
				}

				echo '<div class="mg-upc-array-item" id="' . esc_attr( $option_input_name_key ) . '[div]">';
				echo '<h3>';
				echo '<label><input class="mg-upc-add-array-toggle" type="checkbox"';
				echo ' data-from="' . esc_attr( $pre_fake_name . $option_input_name_key ) . '"';
				echo ' data-to="' . esc_attr( $option_input_name_key ) . '">' . esc_html( $add_new_item ) . '</label>';
				echo '</h3>';
				echo '<div class="mg-upc-array-new-item-slide" style="display: none;">';
				$option_input_name_key = $pre_fake_name . $option_input_name_key;
				foreach ( $args['item_fields'] as $item_option ) {
					// set input name as array item of option.
					$option_input_name = $option_input_name_key . '[' . $item_option['name'] . ']';

					$item = null;
					if ( isset( $item_option['default'] ) ) {
						$item = $item_option['default'];
					}
					$item_option_args             = $this->get_field_render_args(
						$args['section'],
						$item_option,
						false,
						$option_input_name,
						$args['id'],
						false,
						$item
					);
					$item_option_args['readonly'] = false;

					$type            = isset( $item_option['type'] ) ? $item_option['type'] : 'text';
					$callback_render = isset( $item_option['callback'] ) ? $item_option['callback'] : array(
						$this,
						'callback_' . $type,
					);
					if ( ! empty( $item_option['label'] ) ) {
						echo "<label for='" . esc_attr( $option_input_name ) . "'>";
						echo esc_html( $item_option['label'] );
						echo ': </label>';
					}
					call_user_func( $callback_render, $item_option_args );
					echo '<br>';
				}
				echo '</div>';
			}
			echo '</div>';
		}

		/**
		 * Displays a text field for a settings field
		 *
		 * @param array $args settings field args
		 */
		public function callback_text( $args ) {
			$args['value'] = isset( $args['value'] ) ? $args['value'] : $this->get_option( $args['id'], $args['section'], $args['std'] );
			$args['size']  = isset( $args['size'] ) && is_scalar( $args['size'] ) ? $args['size'] : 'regular';
			$args['type']  = isset( $args['type'] ) ? $args['type'] : 'text';

			printf(
				'<input type="%1$s" class="%2$s-text" id="%4$s" name="%4$s" value="%3$s"',
				esc_attr( $args['type'] ),
				esc_attr( $args['size'] ),
				esc_attr( $args['value'] ),
				esc_attr( $args['option_name'] )
			);
			if ( ! empty( $args['placeholder'] ) ) {
				echo ' placeholder="' . esc_attr( $args['placeholder'] ) . '"';
			}
			if ( ! empty( $args['readonly'] ) ) {
				echo ' readonly';
			}
			echo '/>';

			$this->print_field_description( $args );
		}

		/**
		 * Displays a url field for a settings field
		 *
		 * @param array $args settings field args
		 */
		public function callback_url( $args ) {
			$this->callback_text( $args );
		}

		/**
		 * Displays a number field for a settings field
		 *
		 * @param array $args settings field args
		 */
		public function callback_number( $args ) {
			$value = isset( $args['value'] ) ? $args['value'] : $this->get_option( $args['id'], $args['section'], $args['std'] );
			$size  = isset( $args['size'] ) && is_scalar( $args['size'] ) ? $args['size'] : 'regular';
			$type  = isset( $args['type'] ) ? $args['type'] : 'number';

			printf(
				'<input type="%1$s" class="%2$s-number" id="%4$s" name="%4$s" value="%3$s"',
				esc_attr( $type ),
				esc_attr( $size ),
				esc_attr( $value ),
				esc_attr( $args['option_name'] )
			);
			if ( ! empty( $args['min'] ) ) {
				echo ' min="' . esc_attr( $args['min'] ) . '"';
			}
			if ( ! empty( $args['max'] ) ) {
				echo ' max="' . esc_attr( $args['max'] ) . '"';
			}
			if ( ! empty( $args['step'] ) ) {
				echo ' step="' . esc_attr( $args['step'] ) . '"';
			}
			if ( ! empty( $args['placeholder'] ) ) {
				echo ' placeholder="' . esc_attr( $args['placeholder'] ) . '"';
			}
			if ( ! empty( $args['readonly'] ) ) {
				echo ' readonly';
			}
			echo '/>';
			$this->print_field_description( $args );
		}

		/**
		 * Displays a checkbox for a settings field
		 *
		 * @param array $args settings field args
		 */
		public function callback_checkbox( $args ) {

			$value = isset( $args['value'] ) ? $args['value'] : $this->get_option( $args['id'], $args['section'], $args['std'] );

			echo '<fieldset>';
			printf(
				'<label for="tsa-%s">',
				esc_attr( $args['readonly'] ? ' readonly' : $args['option_name'] )
			);

			$value_off = $args['readonly'] ? $value : 'off';
			printf(
				'<input type="hidden" name="%1$s" value="%2$s" />',
				esc_attr( $args['option_name'] ),
				esc_attr( $value_off )
			);
			printf(
				'<input type="checkbox" class="checkbox" id="tsa-%1$s" name="%1$s" value="on" %2$s',
				esc_attr( $args['option_name'] ),
				checked( $value, 'on', false )
			);
			if ( $args['readonly'] ) {
				echo ' disabled';
			}
			echo '/>' . wp_kses( $args['desc'], $this->get_allowed_html() ) . '</label>';
			echo '</fieldset>';
		}

		/**
		 * Displays a multicheckbox for a settings field
		 *
		 * @param array $args settings field args
		 */
		public function callback_multicheck( $args ) {
			$value = isset( $args['value'] ) ? $args['value'] : $this->get_option( $args['id'], $args['section'], $args['std'] );
			echo '<fieldset>';
			printf(
				'<input type="hidden" name="%s" value="" />',
				esc_attr( $args['option_name'] )
			);
			foreach ( $args['options'] as $key => $label ) {
				$checked = empty( $value ) || ! in_array( $key, $value, true ) ? '0' : '1';
				printf(
					'<label for="tsa-%3$s[%1$s]">' .
					'<input type="checkbox" class="checkbox" id="tsa-%3$s[%1$s]" name="%3$s[]" value="%1$s" %2$s />' .
					'%4$s</label><br>',
					esc_attr( $key ),
					checked( $checked, '1', false ),
					esc_attr( $args['option_name'] ),
					wp_kses( $label, $this->get_allowed_html() )
				);
			}

			$this->print_field_description( $args );
			echo '</fieldset>';
		}

		/**
		 * Displays a radio button for a settings field
		 *
		 * @param array $args settings field args
		 */
		public function callback_radio( $args ) {

			$value = isset( $args['value'] ) ? $args['value'] : $this->get_option( $args['id'], $args['section'], $args['std'] );
			echo '<fieldset>';

			foreach ( $args['options'] as $key => $label ) {
				printf(
					'<label for="tsa-%3$s[%1$s]">' .
					'<input type="radio" class="radio" id="tsa-%3$s[%1$s]" name="%3$s" ' .
					'value="%1$s" %2$s /> %4$s</label><br>',
					esc_attr( $key ),
					checked( $value, $key, false ),
					esc_attr( $args['option_name'] ),
					wp_kses( $label, $this->get_allowed_html() )
				);
			}

			$this->print_field_description( $args );
			echo '</fieldset>';
		}

		/**
		 * Displays a selectbox for a settings field
		 *
		 * @param array $args settings field args
		 */
		public function callback_select( $args ) {
			$value = isset( $args['value'] ) ? $args['value'] : $this->get_option( $args['id'], $args['section'], $args['std'] );
			$size  = isset( $args['size'] ) && is_scalar( $args['size'] ) ? $args['size'] : 'regular';

			printf(
				'<select class="%1$s" name="%2$s" id="%2$s">',
				esc_attr( $size ),
				esc_attr( $args['option_name'] )
			);

			foreach ( $args['options'] as $key => $label ) {
				printf(
					'<option value="%s" %s>%s</option>',
					esc_attr( $key ),
					selected( $value, $key, false ),
					esc_html( $label )
				);
			}

			echo '</select>';
			$this->print_field_description( $args );
		}

		/**
		 * Displays a textarea for a settings field
		 *
		 * @param array $args settings field args
		 */
		public function callback_textarea( $args ) {
			$value = isset( $args['value'] ) ? $args['value'] : $this->get_option( $args['id'], $args['section'], $args['std'] );
			$size  = isset( $args['size'] ) && is_scalar( $args['size'] ) ? $args['size'] : 'regular';

			printf(
				'<textarea rows="5" cols="55" class="%1$s-text" id="%2$s" name="%2$s"',
				esc_attr( $size ),
				esc_attr( $args['option_name'] )
			);

			if ( ! empty( $args['placeholder'] ) ) {
				echo ' placeholder="' . esc_attr( $args['placeholder'] ) . '"';
			}
			if ( ! empty( $args['readonly'] ) ) {
				echo ' readonly';
			}
			echo '>' . esc_textarea( $value ) . '</textarea>';
			$this->print_field_description( $args );
		}

		/**
		 * Displays the html for a settings field
		 *
		 * @param array $args settings field args
		 *
		 */
		public function callback_html( $args ) {
			$this->print_field_description( $args );
		}

		/**
		 * Displays a rich text textarea for a settings field
		 *
		 * @param array $args settings field args
		 */
		public function callback_wysiwyg( $args ) {

			$value = isset( $args['value'] ) ? $args['value'] : $this->get_option( $args['id'], $args['section'], $args['std'] );
			$size  = isset( $args['size'] ) && is_scalar( $args['size'] ) ? $args['size'] : '500px';

			echo '<div style="max-width: ' . esc_attr( $size ) . ';">';

			$editor_settings = array(
				'teeny'         => true,
				'textarea_name' => $args['option_name'],
				'textarea_rows' => 10,
			);

			if ( isset( $args['options'] ) && is_array( $args['options'] ) ) {
				$editor_settings = array_merge( $editor_settings, $args['options'] );
			}

			wp_editor( $value, $args['section'] . '-' . $args['id'], $editor_settings );

			echo '</div>';

			$this->print_field_description( $args );
		}

		/**
		 * Displays a file upload field for a settings field
		 *
		 * @param array $args settings field args
		 */
		public function callback_file( $args ) {
			$value = isset( $args['value'] ) ? $args['value'] : $this->get_option( $args['id'], $args['section'], $args['std'] );
			$size  = isset( $args['size'] ) && is_scalar( $args['size'] ) ? $args['size'] : 'regular';
			$label = isset( $args['options']['button_label'] ) ? $args['options']['button_label'] : __( 'Choose File' );

			printf(
				'<input type="text" class="%1$s-text wpsa-url" id="%3$s" name="%3$s" value="%2$s"',
				esc_attr( $size ),
				esc_attr( $value ),
				esc_attr( $args['option_name'] )
			);
			if ( ! empty( $args['readonly'] ) ) {
				echo ' readonly';
			}
			echo '/>';
			if ( empty( $args['readonly'] ) ) {
				echo '<input type="button" class="button mg-upc-browse" value="' . esc_attr( $label ) . '" />';
			}
			$this->print_field_description( $args );
		}

		/**
		 * Displays a password field for a settings field
		 *
		 * @param array $args settings field args
		 */
		public function callback_password( $args ) {

			$value = isset( $args['value'] ) ? $args['value'] : $this->get_option( $args['id'], $args['section'], $args['std'] );
			$size  = isset( $args['size'] ) && is_scalar( $args['size'] ) ? $args['size'] : 'regular';

			printf(
				'<input type="password" class="%1$s-text" id="%3$s" name="%3$s" value="%2$s"',
				esc_attr( $size ),
				esc_attr( $value ),
				esc_attr( $args['option_name'] )
			);

			if ( ! empty( $args['readonly'] ) ) {
				echo ' readonly';
			}
			echo '/>';
			$this->print_field_description( $args );
		}

		/**
		 * Displays a color picker field for a settings field
		 *
		 * @param array $args settings field args
		 */
		public function callback_color( $args ) {

			$value = isset( $args['value'] ) ? $args['value'] : $this->get_option( $args['id'], $args['section'], $args['std'] );
			$size  = isset( $args['size'] ) && is_scalar( $args['size'] ) ? $args['size'] : 'regular';

			printf(
				'<input type="text" class="%1$s-text mg-upc-color-picker-field" id="%4$s" name="%4$s" ' .
				'value="%2$s" data-default-color="%3$s"',
				esc_attr( $size ),
				esc_attr( $value ),
				esc_attr( $args['std'] ),
				esc_attr( $args['option_name'] )
			);
			if ( ! empty( $args['readonly'] ) ) {
				echo ' readonly';
			}
			echo '/>';
			$this->print_field_description( $args );
		}

		/**
		 * Displays a date picker field for a settings field
		 *
		 * @param array $args settings field args
		 */
		public function callback_date( $args ) {

			$value = isset( $args['value'] ) ? $args['value'] : $this->get_option( $args['id'], $args['section'], $args['std'] );
			$size  = isset( $args['size'] ) && is_scalar( $args['size'] ) ? $args['size'] : 'regular';

			printf(
				'<input type="text" class="%1$s-text mg-upc-date-picker-field" id="%4$s" name="%4$s" ' .
				'value="%2$s" data-default-date="%3$s"',
				esc_attr( $size ),
				esc_attr( $value ),
				esc_attr( $args['std'] ),
				esc_attr( $args['option_name'] )
			);

			if ( ! empty( $args['min'] ) ) {
				echo ' data-min-date="' . esc_attr( $args['min'] ) . '"';
			}
			if ( ! empty( $args['max'] ) ) {
				echo ' data-max-date="' . esc_attr( $args['max'] ) . '"';
			}
			if ( ! empty( $args['readonly'] ) ) {
				echo ' readonly';
			}
			echo '/>';
			$this->print_field_description( $args );
		}

		/**
		 * Displays a datetime picker field for a settings field
		 *
		 * @param array $args settings field args
		 */
		public function callback_datetime( $args ) {
			$value = isset( $args['value'] ) ? $args['value'] : $this->get_option( $args['id'], $args['section'], $args['std'] );
			$size  = isset( $args['size'] ) && is_scalar( $args['size'] ) ? $args['size'] : 'regular';

			printf(
				'<input type="text" class="%1$s-text mg-upc-datetime-picker-field" id="%4$s" name="%4$s" ' .
				'value="%2$s" data-default-date="%3$s"/>',
				esc_attr( $size ),
				esc_attr( $value ),
				esc_attr( $args['std'] ),
				esc_html( $args['option_name'] )
			);

			if ( ! empty( $args['min'] ) ) {
				echo ' data-min-date="' . esc_attr( $args['min'] ) . '"';
			}
			if ( ! empty( $args['max'] ) ) {
				echo ' data-max-date="' . esc_attr( $args['max'] ) . '"';
			}
			if ( ! empty( $args['readonly'] ) ) {
				echo ' readonly';
			}
			echo '/>';
			$this->print_field_description( $args );

		}


		/**
		 * Displays a select box for creating the pages select box
		 *
		 * @param array $args settings field args
		 */
		public function callback_pages( $args ) {

			$dropdown_args = array(
				'selected' => isset( $args['value'] ) ? $args['value'] : $this->get_option( $args['id'], $args['section'], $args['std'] ),
				'name'     => $args['option_name'],
				'id'       => $args['option_name'],
				'echo'     => 1,
			);

			wp_dropdown_pages( $dropdown_args );
		}

		/**
		 * Sanitize callback option for as_array section
		 *
		 * @param $wp_value
		 * @param $wp_option
		 *
		 * @return mixed
		 */
		public function sanitize_options_section_as_array( $wp_value, $wp_option ) {

			if ( ! $wp_value ) {
				return $wp_value;
			}

			if ( is_array( $wp_value ) ) { //as_array section
				foreach ( $wp_value as $option_slug => $option_value ) {
					$sanitize_callback = $this->get_sanitize_callback( $wp_option, $option_slug );
					if ( $sanitize_callback ) {
						$wp_value[ $option_slug ] = call_user_func( $sanitize_callback, $wp_value[ $option_slug ] );
					}
				}
			} else {
				add_settings_error(
					$wp_option,
					$wp_option . 'ERR',
					'Error: ' . $wp_option . ' Value require an array'
				);
			}

			return $wp_value;
		}

		/**
		 * Sanitize callback for array type
		 *
		 * @param $wp_value
		 * @param $section
		 * @param $wp_option
		 *
		 * @return mixed
		 */
		public function sanitize_options_array_type( $wp_value, $section, $wp_option ) {

			$old_value = get_option( $wp_option );

			//Get config
			$option_array_config = array();
			foreach ( $this->settings_fields[ $section ] as $option_array ) {
				if ( $option_array['name'] === $wp_option ) {
					$option_array_config = $option_array;
					break;
				}
			}

			//Check empty
			if ( ! $wp_value ) {
				if ( isset( $option_array_config['no_empty'] ) && $option_array_config['no_empty'] ) {
					add_settings_error(
						$wp_option,
						$wp_option . 'ERR',
						sprintf(
						// translators: %s is array config label
							__( 'Error on %s: The setting cannot be empty.' ),
							$option_array_config['label']
						)
					);

					return $old_value;
				}

				return $wp_value;
			}

			if ( is_array( $wp_value ) ) { //array field
				foreach ( $wp_value as $index_arr => $option_value ) {
					if ( ! is_array( $option_value ) ) {
						add_settings_error(
							$wp_option,
							$wp_option . 'ERR',
							'Error [' . $wp_option . ']:  Value require an array of objects'
						);

						return $old_value;
					}
					if ( ! is_int( $index_arr ) && ! ctype_digit( $index_arr ) ) {
						add_settings_error(
							$wp_option,
							$wp_option . 'ERR',
							'Error [' . $wp_option . ']:  Key require integer'
						);

						return $old_value;
					}

					//Check if has all fields
					foreach ( $option_array_config['item_fields'] as $field ) {
						if ( ! isset( $option_value[ $field['name'] ] ) ) {
							add_settings_error(
								$wp_option,
								$wp_option . 'ERR',
								'Error [' . $wp_option . ']:  All items props are required.'
							);

							return $old_value;
						}
					}

					foreach ( $option_value as $item_option_slug => $item_option_value ) {
						$sanitize_callback = $this->get_sanitize_callback_field_array( $section, $wp_option, $item_option_slug, $index_arr );
						if ( $sanitize_callback ) {
							$wp_value[ $index_arr ][ $item_option_slug ] = call_user_func( $sanitize_callback, $item_option_value );
						}
					}
				}
			} else {
				add_settings_error(
					$wp_option,
					$wp_option . 'ERR',
					'Error [' . $wp_option . ']: Value require an array'
				);

				return $old_value;
			}

			if ( isset( $option_array_config['no_empty'] ) && $option_array_config['no_empty'] ) {
				if ( empty( $wp_value ) ) {
					add_settings_error(
						$wp_option,
						$wp_option . 'ERR',
						sprintf(
						// translators: %s is array config label
							__( 'Error on %s: The setting cannot be empty.', 'user-post-collections' ),
							$option_array_config['label']
						)
					);

					return $old_value;
				}
			}
			//Check unique fields
			foreach ( $option_array_config['item_fields'] as $field ) {
				if ( isset( $field['unique'] ) && $field['unique'] ) {
					$no_repeat = array();
					foreach ( $wp_value as $index_arr => $option_value ) {
						if ( in_array( $option_value[ $field['name'] ], $no_repeat, true ) ) {
							add_settings_error(
								$wp_option,
								$wp_option . 'ERR',
								sprintf(
								// translators: %1$s is array config label, %2$s is field of an item
									__( 'Error on %1$s: The %2$s property must be unique.' ),
									$option_array_config['label'],
									$field['label']
								)
							);

							return $old_value;
						}
						$no_repeat[] = $option_value[ $field['name'] ];
					}
				}
			}

			if (
				isset( $option_array_config['sanitize_callback'] ) &&
				is_callable( $option_array_config['sanitize_callback'] )
			) {

				$function_params = array(
					$wp_value,
					$section . '[' . $section . ']',
					$old_value,
					$option_array_config,
				);
				if ( ! isset( $option_array_config['sanitize_callback_params'] ) ) {
					$option_array_config['sanitize_callback_params'] = 1;
				}
				$function_params = array_slice( $function_params, 0, $option_array_config['sanitize_callback_params'] );

				$wp_value = call_user_func_array( $option_array_config['sanitize_callback'], $function_params );
			}

			return $wp_value;
		}

		/**
		 * Sanitize callback for Settings API, simple option, not as_array section, not array type
		 *
		 * @param $wp_value
		 * @param $section
		 * @param $wp_option
		 *
		 * @return mixed
		 */
		public function sanitize_options( $wp_value, $section, $wp_option ) {

			if ( ! $wp_value ) {
				return $wp_value;
			}

			$sanitize_callback = $this->get_sanitize_callback( $section, $wp_option );
			if ( $sanitize_callback ) {
				$wp_value = call_user_func( $sanitize_callback, $wp_value );
			}

			return $wp_value;
		}

		/**
		 * Get sanitization callback for given option slug
		 *
		 * @param string      $section The section id
		 * @param string      $slug The option name
		 * @param string      $item_option_slug The property of item
		 * @param string|bool $array_idx Optional. Index of item. Default false.
		 *
		 * @return mixed string or bool false
		 */
		public function get_sanitize_callback_field_array( $section, $slug = '', $item_option_slug = '', $array_idx = false ) {
			if ( empty( $slug ) ) {
				return false;
			}

			$options = $this->settings_fields[ $section ];
			foreach ( $options as $option_array ) {
				if ( $slug !== $option_array['name'] ) {
					continue;
				}
				$sub_option = $option_array['item_fields'][ $item_option_slug ];

				if ( isset( $sub_option['sanitize_callback'] ) && is_callable( $sub_option['sanitize_callback'] ) ) {
					// add rest of params
					return function ( $option_value ) use ( $option_array, $section, $array_idx, $item_option_slug ) {
						$sub_option      = $option_array['item_fields'][ $item_option_slug ];
						$old_value       = $this->get_option( $option_array['name'], $section, '', $array_idx );
						$function_params = array(
							$option_value,
							$option_array['name'] . '[' . $array_idx . '][' . $item_option_slug . ']',
							isset( $old_value[ $item_option_slug ] ) ? $old_value[ $item_option_slug ] : '',
							$sub_option,
						);

						if ( ! isset( $sub_option['sanitize_callback_params'] ) ) {
							$sub_option['sanitize_callback_params'] = 1;
						}
						$function_params = array_slice( $function_params, 0, $sub_option['sanitize_callback_params'] );

						return call_user_func_array( $sub_option['sanitize_callback'], $function_params );
					};
				}

				return false;
			}

			return false;
		}

		/**
		 * Get sanitization callback for given option
		 *
		 * @param string $section The section id
		 * @param string $slug The option name
		 *
		 * @return mixed string, function or bool false
		 */
		public function get_sanitize_callback( $section, $slug = '' ) {
			if ( empty( $slug ) ) {
				return false;
			}

			$options = $this->settings_fields[ $section ];
			foreach ( $options as $option ) {
				if ( $option['name'] !== $slug ) {
					continue;
				}

				if ( 'array' === $option['type'] ) {
					return function ( $option_values ) use ( $option, $section ) {
						$old_values = $this->get_option( $option['name'], $section, array() );
						foreach ( $option_values as $array_idx => $new_value ) {
							foreach ( $option['item_fields'] as $item_option_value ) {
								$item_option_slug = $item_option_value['name'];
								if ( isset( $item_option_value['sanitize_callback'] ) && is_callable( $item_option_value['sanitize_callback'] ) ) {
									// add rest of params
									$old_value       = $old_values[ $array_idx ][ $item_option_slug ];
									$function_params = array(
										$new_value[ $item_option_slug ],
										$section . '[' . $option['name'] . '][' . $array_idx . ']',
										$old_value,
										$item_option_value,
									);
									if ( ! isset( $item_option_value['sanitize_callback_params'] ) ) {
										$item_option_value['sanitize_callback_params'] = 1;
									}
									$function_params                                  = array_slice( $function_params, 0, $item_option_value['sanitize_callback_params'] );
									$option_values[ $array_idx ][ $item_option_slug ] = call_user_func_array( $item_option_value['sanitize_callback'], $function_params );
								}
							}
						}

						return $option_values;
					};
				}

				if ( isset( $option['sanitize_callback'] ) && is_callable( $option['sanitize_callback'] ) ) {
					// add rest of params
					return function ( $option_value ) use ( $option, $section ) {
						$old_value       = $this->get_option( $option['name'], $section, '' );
						$function_params = array(
							$option_value,
							$section . '[' . $option['name'] . ']',
							$old_value,
							$option,
						);
						if ( ! isset( $option['sanitize_callback_params'] ) ) {
							$option['sanitize_callback_params'] = 1;
						}
						$function_params = array_slice( $function_params, 0, $option['sanitize_callback_params'] );

						return call_user_func_array( $option['sanitize_callback'], $function_params );
					};
				}

				return false;
			}

			return false;
		}

		/**
		 * Get the value of a settings field
		 *
		 * @param string      $option_name Settings field name
		 * @param string      $section_id  The   Section name this field belongs to
		 * @param string      $default     Optional. Default text if it's not found
		 * @param string|bool $array_idx   Optional. Index of item, for array type
		 *
		 * @return string|array
		 */
		public function get_option( $option_name, $section_id, $default = '', $array_idx = false ) {
			$section = $this->settings_sections[ $section_id ];
			if ( true === $section['as_array'] ) {
				$wp_option = get_option( $section_id );
				if ( isset( $wp_option[ $option_name ] ) ) {
					if ( false !== $array_idx ) { //For dynamic array
						if (
							is_array( $wp_option[ $option_name ] ) &&
							isset( $wp_option[ $option_name ][ $array_idx ] )
						) {
							return $wp_option[ $option_name ][ $array_idx ];
						} else {
							return $default;
						}
					}

					return $wp_option[ $option_name ];
				} else {
					return $default;
				}
			}
			$wp_option = get_option( $option_name, $default );
			if ( false !== $array_idx ) { //For dynamic array
				if ( is_array( $wp_option ) && isset( $wp_option[ $array_idx ] ) ) {
					return $wp_option[ $array_idx ];
				} else {
					return $default;
				}
			}

			return $wp_option;
		}

		/**
		 * Show navigations as tab
		 *
		 * Shows all the settings section labels as tab
		 */
		public function show_navigation() {
			echo '<h2 class="nav-tab-wrapper mg-upc-nav-tab-wrapper">';

			$count = count( $this->settings_sections );

			// don't show the navigation if only one section exists
			if ( 1 === $count ) {
				return;
			}

			foreach ( $this->settings_sections as $tab ) {
				printf(
					'<a href="#%1$s" class="nav-tab" id="%1$s-tab">%2$s</a>',
					esc_attr( $tab['id'] ),
					esc_attr( $tab['title'] )
				);
			}

			echo '</h2>';
		}

		/**
		 * Show the section settings forms
		 *
		 * This function displays every sections in a different form
		 */
		public function show_forms() {
			?>
			<div class="metabox-holder">
				<?php
				foreach ( $this->settings_sections as $form ) {
					?>
					<div id="<?php echo esc_attr( $form['id'] ); ?>" class="group" style="display: none;">
						<?php
						if ( isset( $form['form_callback'] ) && is_callable( $form['form_callback'] ) ) {
							call_user_func( $form['form_callback'] );
						} else {
							?>
							<form method="post" action="options.php">
								<?php
								do_action( 'wsa_form_top_' . $form['id'], $form );
								settings_fields( $form['id'] );
								do_settings_sections( $form['id'] );
								do_action( 'wsa_form_bottom_' . $form['id'], $form );
								if ( isset( $this->settings_fields[ $form['id'] ] ) ) :
									?>
									<div style="padding-left: 10px">
										<?php submit_button(); ?>
									</div>
								<?php endif; ?>
							</form>
							<?php
						}
						?>
					</div>
				<?php } ?>
			</div>
			<?php
		}

	}

endif;
