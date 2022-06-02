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
				test: /\.html$/i,
				loader: "html-loader",
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
	}
};
