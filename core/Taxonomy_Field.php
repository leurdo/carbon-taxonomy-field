<?php

namespace Carbon_Field_Taxonomy;

use Carbon_Fields\Field\Predefined_Options_Field;

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
		wp_enqueue_style( 'carbon-field-Taxonomy', $root_uri . '/build/bundle.min.css', array(), TAXONOMY_VERSION );

		// Enqueue field scripts.
		wp_enqueue_script( 'carbon-field-Taxonomy', $root_uri . '/build/bundle.min.js', array( 'carbon-fields-core' ), TAXONOMY_VERSION, true );
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
		$value = null;
		if ( isset( $input[ $this->get_name() ] ) ) {
			$value = $input[ $this->get_name() ];
		}

		return $this->set_value( $value );
	}

	/**
	 * {@inheritDoc}
	 */
	public function to_json( $load ) {
		$field_data  = parent::to_json( $load );
		$raw_options = $this->get_options();

		$options     = $this->parse_options( $raw_options, true );
		$value       = intval( $this->get_value() );
		$term = get_term( $value );
		$value_array = ! is_wp_error( $term ) ? [
			'value' => $term->term_id,
			'label' => $term->name,
		] : null;

		$field_data = array_merge(
			$field_data,
			[
				'value'   => $value_array,
				'options' => $options,
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
				'number'     => 5,
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
}
