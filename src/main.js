/**
 * External dependencies.
 */
import { Component } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import AsyncCreatableSelect from 'react-select/async-creatable';

class TaxonomyField extends Component {

	state = {
		value: undefined,
		isLoading: false,
	};

	loadOptions = inputValue => {
		return new Promise((resolve, reject) => {
			wp.ajax.post('carbon_taxonomy_get_filtered_terms', {
				inputValue: inputValue,
				tax: this.props.field.tax,
				nonce: window.carbon_taxonomy.nonce,
			}).done( response => {
				resolve(response.options);
			}).fail( () => {
				reject( __( 'An error occurred while trying to fetch files data.', 'carbon-fields-ui' ) );
			} );
		})
	}

	handleCreate = inputValue => {
		let { value } = this.state;
		if (value === undefined) {
			value = this.props.field.value;
		}
		this.setState({
			isLoading: true,
		});
		wp.ajax.post('carbon_taxonomy_create_term', {
			inputValue: inputValue,
			tax: this.props.field.tax,
			nonce: window.carbon_taxonomy.nonce,
		}).done( response => {
			const result = this.props.field.multiple ? value.concat(response.option) : response.option;
			this.setState({
				value: result,
				isLoading: false,
			});
		}).fail( () => {
			reject( __( 'An error occurred while trying to fetch files data.', 'carbon-fields-ui' ) );
		} );
	}

	handleChange = (newValue) => {
		this.setState({
			value: newValue,
		});
	}

	/**
	 * Renders the component.
	 *
	 * @return {Object}
	 */
	render() {
		const { name, field } = this.props;
		const { value, isLoading } = this.state;
		const loadingMessage = () => {
			return field.loading;
		}
		const createLabel = (inputValue) => {
			return field.create + ' ' + inputValue;
		}
		const inputId = 'input_' + field.id;

		return (
			<AsyncCreatableSelect
				id={field.id}
				name={name}
				defaultValue={field.value}
				defaultOptions={field.options}
				loadOptions={this.loadOptions}
				classNamePrefix="react-taxonomy"
				isClearable
				onCreateOption={this.handleCreate}
				onChange={this.handleChange}
				value={value}
				isLoading={isLoading}
				isDisabled={isLoading}
				placeholder={field.placeholder}
				loadingMessage={loadingMessage}
				inputId={inputId}
				formatCreateLabel={createLabel}
				delimiter="|"
				isMulti={field.multiple}
			/>
		);
	}

}

export default TaxonomyField;
