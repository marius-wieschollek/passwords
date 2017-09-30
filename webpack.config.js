let path = require('path');
let webpack = require('webpack');
let ExtractTextPlugin = require("extract-text-webpack-plugin");
let OptimizeCSSPlugin = require('optimize-css-assets-webpack-plugin');

module.exports = {
    entry  : "./src/js/main.js",
    output : {
        path    : __dirname,
        filename: "./src/js/Static/passwords.js"
    },
    resolve: {
        extensions: ['.js', '.vue', '.json'],
        alias     : {
            'vue$': 'vue/dist/vue.esm.js',
            '@'   : path.join(__dirname, 'src'),
            '@js' : path.join(__dirname, 'src/js'),
            '@vc' : path.join(__dirname, 'src/js/Components'),
        }
    },
    module : {
        loaders: [
            {
                test   : /\.vue$/,
                loader : 'vue-loader',
                options: {
                    extractCSS: true,
                    loaders   : {
                        scss: ExtractTextPlugin.extract(
                            {
                                use     : [
                                    {
                                        loader : 'css-loader',
                                        options: {minimize: true, sourceMap: false}
                                    }, {
                                        loader : 'sass-loader',
                                        options: {minimize: true, sourceMap: false}
                                    }, {
                                        loader : 'sass-resources-loader',
                                        options: {resources: path.resolve(__dirname, './src/css/Partials/_variables.scss')}
                                    }
                                ],
                                fallback: 'vue-style-loader'
                            }
                        )
                    }
                }
            }
        ]
    },
    plugins: [
        new ExtractTextPlugin('./src/css/Static/passwords.css'),
        new OptimizeCSSPlugin(
            {
                cssProcessorOptions: {
                    safe: true
                }
            }
        )
    ]
};