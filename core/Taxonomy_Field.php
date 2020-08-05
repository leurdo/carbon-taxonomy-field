<?php

namespace Carbon_Field_Taxonomy;

use Carbon_Fields\Field\Predefined_Options_Field;
use Carbon_Fields\Helper\Delimiter;
use Carbon_Fields\Helper\Helper;
use Carbon_Fields\Value_Set\Value_Set;

/**
 * Class Taxonomy_Field
 *
 * @package Carbon_Field_Taxonomy
 */
class Taxonomy_Field extends Predefined_Options_Field {
	/**
	 * Valid taxonomy
	 *
	 * @var string
	 */
	protected $tax = '';

	/**
	 * Input placeholder
	 *
	 * @var string
	 */
	protected $placeholder = '';

	/**
	 * Create label text
	 *
	 * @var string
	 */
	protected $create = '';

	/**
	 * Loading label text
	 *
	 * @var string
	 */
	protected $loading = '';

	/**
	 * Multiselect flag
	 *
	 * @var bool
	 */
	protected $multiple = false;

	/**
	 * Create a field from a certain type with the specified label.
	 *
	 * @param string $type  Field type
	 * @param string $name  Field name
	 * @param string $label Field label
	 */
	public function __construct( $type, $name, $label ) {
		$this->set_value_set( new Value_Set( Value_Set::TYPE_MULTIPLE_VALUES ) );
		parent::__construct( $type, $name, $label );
	}

	/**
	 * Instance initialization when in the admin area
	 * Called during field boot
	 */
	public function init() {
		add_action( 'wp_ajax_whisk_get_filtered_terms', [ $this, 'whisk_get_filtered_terms' ] );
		add_action( 'wp_ajax_whisk_create_term', [ $this, 'whisk_create_term' ] );
	}

	/**
	 * Prepare the field type for use.
	 * Called once per field type when activated.
	 *
	 * @static
	 * @access public
	 *
	 * @return void
	 */
	public static function field_type_activated() {
		$dir    = DIR . '/languages/';
		$locale = get_locale();
		$path   = $dir . $locale . '.mo';
		load_textdomain( 'carbon-field-Taxonomy', $path );
	}

	/**
	 * Enqueue scripts and styles in admin.
	 * Called once per field type.
	 *
	 * @static
	 * @access public
	 *
	 * @return void
	 */
	public static function admin_enqueue_scripts() {
		$root_uri = \Carbon_Fields\Carbon_Fields::directory_to_url( \Carbon_Field_Taxonomy\DIR );

		// Enqueue field styles.
		wp_enqueue_style( 'carbon-field-Taxonomy', $root_uri . '/build/bundle.min.css', array(), CARBON_TAXONOMY_VERSION );

		// Enqueue field scripts.
		wp_enqueue_script( 'carbon-field-Taxonomy', $root_uri . '/build/bundle.min.js', array( 'carbon-fields-core' ), CARBON_TAXONOMY_VERSION, true );
		wp_localize_script(
			'carbon-field-Taxonomy',
			'carbon_taxonomy',
			[
				'nonce' => wp_create_nonce( 'carbon_taxonomy' ),
			]
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function set_value_from_input( $input ) {
		if ( ! isset( $input[ $this->get_name() ] ) ) {
			return $this->set_value( array() );
		}

		$value_delimiter = '|';
		$options_values = $this->get_options_values();

		$value = stripslashes_deep( $input[ $this->get_name() ] );
		$value = Delimiter::split( $value, $value_delimiter );
		$value = Helper::get_valid_options( $value, $options_values );

		return $this->set_value( $value );
	}

	/**
	 * {@inheritDoc}
	 */
	public function to_json( $load ) {
		$field_data  = parent::to_json( $load );
		$raw_options = $this->get_options();

		$options     = $this->parse_options( $raw_options, true );
		$value       = $this->get_value();
		$value_array = [];

		if ( $value && is_array( $value ) ) {
			foreach ( $value as $item ) {
				$term = get_term( $item );
				if ( ! is_wp_error( $term ) ) {
					$value_array[] = [
						'value' => $term->term_id,
						'label' => $term->name,
					];
				}
			}
		}

		$field_data = array_merge(
			$field_data,
			[
				'value'   => $value_array,
				'options' => $options,
				'placeholder' => $this->placeholder ? $this->placeholder : __('Select...', 'carbon-field-Taxonomy'),
				'loading' => $this->loading ? $this->loading : __('Loading...', 'carbon-field-Taxonomy'),
				'create' => $this->create ? $this->create : __('Create', 'carbon-field-Taxonomy'),
				'multiple' => $this->multiple,
			]
		);

		return $field_data;
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_options() {
		$options = [];
		$terms   = get_terms(
			[
				'taxonomy'   => $this->tax,
				'hide_empty' => false,
				'number'     => 20,
				'orderby'    => 'count',
			]
		);
		if ( $terms ) {
			foreach ( $terms as $term ) {
				$options[ $term->term_id ] = $term->name;
			}
		}

		return $options;
	}

	private function get_all_options() {
		$options = [];
		$terms   = get_terms(
			[
				'taxonomy'   => $this->tax,
				'hide_empty' => false,
			]
		);
		if ( $terms ) {
			foreach ( $terms as $term ) {
				$options[ $term->term_id ] = $term->name;
			}
		}

		return $options;
	}

	/**
	 * Retrieve the current options' values only.
	 *
	 * @return array $options
	 */
	protected function get_options_values() {
		$options = $this->parse_options( $this->get_all_options() );
		return wp_list_pluck( $options, 'value' );
	}

	/**
	 * Term search ajax callback
	 *
	 * @return string
	 */
	public function whisk_get_filtered_terms() {
		check_ajax_referer( 'carbon_taxonomy', 'nonce' );

		$options = [];
		$search  = isset( $_POST['inputValue'] ) ? sanitize_text_field( wp_unslash( $_POST['inputValue'] ) ) : '';
		if ( ! $search ) {
			return wp_send_json_success(
				[
					'options' => $this->parse_options( $this->get_options(), true ),
				]
			);
		}

		$terms = get_terms(
			[
				'taxonomy'   => $this->tax,
				'hide_empty' => false,
				'search'     => $search,
			]
		);
		if ( $terms ) {
			foreach ( $terms as $term ) {
				$options[ $term->term_id ] = $term->name;
			}
		}
		return wp_send_json_success(
			[
				'options' => $this->parse_options( $options, true ),
			]
		);
	}

	public function whisk_create_term() {
		check_ajax_referer( 'carbon_taxonomy', 'nonce' );

		$option = [];
		$new_option_name = isset( $_POST['inputValue'] ) ? sanitize_text_field( wp_unslash( $_POST['inputValue'] ) ) : '';
		$insert_data = wp_insert_term(
			$new_option_name,
			$this->tax
		);
		if ( ! is_wp_error( $insert_data ) ) {
			$term_id            = $insert_data['term_id'];
			$option[ $term_id ] = $new_option_name;
		}

		return wp_send_json_success(
			[
				'option' => $this->parse_options( $option, true ),
			]
		);
	}

	/**
	 * Set taxonomy from where to get select options
	 *
	 * @param string $tax
	 * @return Taxonomy_Field
	 */
	public function set_tax( $tax ) {
		$this->tax = $tax;
		return $this;
	}

	/**
	 * Set input placeholder
	 *
	 * @param string $placeholder
	 * @return Taxonomy_Field
	 */
	public function set_placeholder( $placeholder ) {
		$this->placeholder = $placeholder;
		return $this;
	}

	/**
	 * Set create term label
	 *
	 * @param string $create
	 * @return Taxonomy_Field
	 */
	public function set_create( $create ) {
		$this->tax = $create;
		return $this;
	}

	/**
	 * Set loading label
	 *
	 * @param string $loading
	 * @return Taxonomy_Field
	 */
	public function set_loading( $loading ) {
		$this->tax = $loading;
		return $this;
	}

	/**
	 * Set multiselect
	 *
	 * @param string $loading
	 * @return Taxonomy_Field
	 */
	public function set_multiple( $is_multiple ) {
		$this->multiple = $is_multiple;
		return $this;
	}
}
