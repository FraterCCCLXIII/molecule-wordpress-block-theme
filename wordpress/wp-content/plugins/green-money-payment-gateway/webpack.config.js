const defaultConfig = require('@wordpress/scripts/config/webpack.config')
const DependencyExtractionWebpackPlugin = require('@woocommerce/dependency-extraction-webpack-plugin')
const MiniCssExtractPlugin = require('mini-css-extract-plugin')
const path = require('path')
const TsconfigPathsPlugin = require('tsconfig-paths-webpack-plugin')

const wcDepMap = {
	'@woocommerce/blocks-registry': ['wc', 'wcBlocksRegistry'],
	'@woocommerce/settings': ['wc', 'wcSettings'],
	'@wordpress/element': ['wp', 'element'],
	'@wordpress/data': ['wp', 'data'],
	'@wordpress/plugins': ['wp', 'plugins'],
}

const wcHandleMap = {
	'@woocommerce/blocks-registry': 'wc-blocks-registry',
	'@woocommerce/settings': 'wc-settings',
	'@wordpress/element': 'wp-element',
	'@wordpress/data': 'wp-data',
	'@wordpress/plugins': 'wp-plugins',
}

const requestToExternal = (request) => wcDepMap[request]
const requestToHandle = (request) => wcHandleMap[request]

const isProduction = process.env.NODE_ENV === 'production'

module.exports = {
	...defaultConfig,
	entry: { greenpay: path.resolve(__dirname, 'src/index.tsx') },
	output: { path: path.resolve(__dirname, 'dist'), filename: '[name].js', publicPath: 'http://localhost:3000/dist/' },
	resolve: {
		extensions: ['.js', '.jsx', '.ts', '.tsx', '.scss'],
		plugins: [new TsconfigPathsPlugin({ configFile: path.resolve(__dirname, 'tsconfig.json') })],
	},
	resolveLoader: {
		modules: ['node_modules'], // Ensure your loaders are used, not WP-internal ones
	},
	module: { ...defaultConfig.module, rules: [...defaultConfig.module.rules] },
	plugins: [
		...defaultConfig.plugins.filter((plugin) => plugin.constructor.name !== 'DependencyExtractionWebpackPlugin'),
		new DependencyExtractionWebpackPlugin({ requestToExternal, requestToHandle }),
		...(isProduction ? [new MiniCssExtractPlugin({ filename: '[name].css' })] : []),
	],
	externals: { react: 'React', 'react-dom': 'ReactDOM' },
	devServer: {
		static: { directory: path.resolve(__dirname, 'dist') },
		port: 3000,
		hot: true,
		headers: { 'Access-Control-Allow-Origin': '*' },
		devMiddleware: { publicPath: 'http://localhost:3000/dist/' },
	},
}
