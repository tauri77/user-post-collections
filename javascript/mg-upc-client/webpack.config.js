const path = require('path');
const MiniCSSExtractPlugin = require('mini-css-extract-plugin');

module.exports = {
	plugins: [
		new MiniCSSExtractPlugin({
			filename: "./css/styles.css",
		})
	],
	module: {
		rules: [
			{
				test: /\.scss$/,
				use: [
					MiniCSSExtractPlugin.loader,
					"css-loader",
					"sass-loader"
				]
			},
			{
				test: /\.js$/,
				exclude: /node_modules/,
				use: {
					loader: 'babel-loader'
				}
			}
		]
	},
	"resolve": {
		"alias": {
			"react": "preact/compat",
			"react-dom/test-utils": "preact/test-utils",
			"react-dom": "preact/compat",     // Must be below test-utils
			"react/jsx-runtime": "preact/jsx-runtime",
			"react-a11y-dialog": "react-a11y-dialog"
		},
	},
	entry: {
		'main': './src/index.js',
		'admin': './src-admin/admin.js',
	},
	output: {
		filename: '[name].js',
		path: path.resolve(__dirname, 'dist'),
		clean: true
	},
};
