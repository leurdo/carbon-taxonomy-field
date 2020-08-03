/**
 * External dependencies.
 */
import { registerFieldType } from '@carbon-fields/core';

/**
 * Internal dependencies.
 */
import './style.scss';
import TaxonomyField from './main';

registerFieldType( 'taxonomy', TaxonomyField );
