import { registerBlockType } from '@wordpress/blocks';
import Edit from './edit';
import metadata from './block.json';

registerBlockType( metadata.name, {
	icon: {
		background: '#FFFFFF',
		foreground: '#658B24',
		src: 'id',
	},
	edit: Edit,
} );
