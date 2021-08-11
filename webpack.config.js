const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
defaultConfig.module.rules.splice( 0, 1 ); // JSをトランスパイルするルールを削除。下の独自ルールでPOTファイルを上書きして空にしてしまう。
module.exports = {
	mode: 'production',
	...defaultConfig,
	entry: __dirname + '/blocks/create-table/src/index.js',
	output: {
		path: __dirname + '/blocks/create-table/build/',
		filename: 'block-build.js',
	},
	module: {
		...defaultConfig.module,
		rules: [
			...defaultConfig.module.rules,
			{
				test: /\.js$/,
				exclude: /(node_modules|bower_components)/,
				use: {
					loader: 'babel-loader',
					options: {
						cacheDirectory: false, // キャッシュをOFF。理由：vk-blocks-js.pot を消した時に変更箇所以外の文字列が抽出されなくなる。
						babelrc: false, // babelrcを反映させない
						configFile: false, // babel.config.jsonを反映させない
						presets: [ '@wordpress/default' ],
					},
				},
			},
		],
	},
};
