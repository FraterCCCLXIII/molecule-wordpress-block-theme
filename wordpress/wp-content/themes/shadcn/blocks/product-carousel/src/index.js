import { registerBlockType } from '@wordpress/blocks';
import { InnerBlocks } from '@wordpress/block-editor';
import './editor.css';
import metadata from '../block.json';
import Edit from './edit';

registerBlockType( metadata.name, {
	edit: Edit,
	// Save inner blocks HTML so render.php receives it as $content.
	save: () => <InnerBlocks.Content />,
} );
