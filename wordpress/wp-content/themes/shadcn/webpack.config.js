const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const path = require( 'path' );

module.exports = [
	{
		...defaultConfig,
		entry: {
			index: path.resolve( __dirname, 'blocks/mobile-drawer/src/index.js' ),
		},
		output: {
			...defaultConfig.output,
			path: path.resolve( __dirname, 'blocks/mobile-drawer/build' ),
		},
	},
	{
		...defaultConfig,
		entry: {
			index: path.resolve( __dirname, 'blocks/icon-link/src/index.js' ),
		},
		output: {
			...defaultConfig.output,
			path: path.resolve( __dirname, 'blocks/icon-link/build' ),
		},
	},
];
