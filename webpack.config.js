module.exports = {
    mode: 'production',
    devtool: 'source-map',
    entry: "./blocks/create-table/index.js",
    output: {
        filename: "./blocks/create-table/build.js"
    },
    module: {
        rules: [
            {
                test: /\.js$/,
                exclude: /(node_modules|bower_components)/,
                use: {
                    loader: 'babel-loader',
                    options: {
                        presets: ['@babel/preset-env'],
                        plugins: [
                            '@babel/plugin-transform-react-jsx',
                            [
                                // JSをスキャンして、potを作成/アップデート
                                '@wordpress/babel-plugin-makepot',
                                {
                                    'output': `./languages/vk-google-job-posting-manager-json.pot`
                                }
                            ]
                        ]
                    }
                }
            }
        ]
    }
}
