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

	loadOptions(inputValue) {
		return new Promise((resolve, reject) => {
			wp.ajax.post('whisk_get_filtered_terms', {
				inputValue: inputValue,
				nonce: window.carbon_taxonomy.nonce,
			}).done( response => {
				resolve(response.options);
			}).fail( () => {
				reject( __( 'An error occurred while trying to fetch files data.', 'carbon-fields-ui' ) );
			} );
		})
	}

	handleCreate = inputValue => {
		this.setState({
			isLoading: true,
		});
		wp.ajax.post('whisk_create_term', {
			inputValue: inputValue,
			nonce: window.carbon_taxonomy.nonce,
		}).done( response => {
			this.setState({
				value: response.option,
				isLoading: false,
			});
		}).fail( () => {
			reject( __( 'An error occurred while trying to fetch files data.', 'carbon-fields-ui' ) );
		} );
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
					value={value}
					isLoading={isLoading}
					isDisabled={isLoading}
					placeholder={field.placeholder}
					loadingMessage={loadingMessage}
					inputId="react-taxonomy"
					formatCreateLabel={createLabel}
				/>
		);
	}

}

export default TaxonomyField;
