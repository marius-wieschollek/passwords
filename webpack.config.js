let webpack = require('webpack'),
    UglifyJSPlugin = require('uglifyjs-webpack-plugin'),
    CopyWebpackPlugin = require('copy-webpack-plugin'),
    ExtractTextPlugin = require('extract-text-webpack-plugin'),
    ProgressBarPlugin = require('progress-bar-webpack-plugin'),
    OptimizeCSSPlugin = require('optimize-css-assets-webpack-plugin');

module.exports = (env) => {
    let production = !!(env && env.production);
    console.log('Production: ', production);

    let plugins = [
        new webpack.DefinePlugin(
            {
                'process.env': {
                    NODE_ENV: production ? '"production"':'"development"',
                    NIGHTLY_FEATURES: !!(env && env.features)
                }
            }
        ),
        new ExtractTextPlugin('css/[name].css'),
        new CopyWebpackPlugin(
            [
                {from: `${__dirname}/src/js/Helper/utility.js`, to: `${__dirname}/src/js/Static/utility.js`},
                {from: `${__dirname}/src/js/Helper/compatibility.js`, to: `${__dirname}/src/js/Static/compatibility.js`}
            ]
        ),
        new webpack.optimize.CommonsChunkPlugin({name: 'common', minChunks: Infinity})
    ];

    if(production) {
        plugins.push(new OptimizeCSSPlugin({cssProcessorOptions: {safe: true}}));
        plugins.push(
            new UglifyJSPlugin(
                {
                    uglifyOptions: {
                        beautify: false,
                        ecma    : 6,
                        compress: true,
                        comments: false,
                        ascii   : true
                    },
                    cache        : true,
                    parallel     : true
                }
            )
        );
        plugins.push(new ProgressBarPlugin());
    }

    return {
        entry  : {
            app     : `${__dirname}/src/js/app.js`,
            admin   : `${__dirname}/src/js/admin.js`,
            personal: `${__dirname}/src/js/personal.js`
        },
        output : {
            path         : `${__dirname}/src/`,
            filename     : 'js/Static/[name].js',
            chunkFilename: 'js/Static/[name].[hash].js'
        },
        resolve: {
            modules   : ['node_modules', 'src'],
            extensions: ['.js', '.vue', '.scss'],
            alias     : {
                'vue$' : 'vue/dist/vue.esm.js',
                '@'    : `${__dirname}/src`,
                '@js'  : `${__dirname}/src/js`,
                '@vue' : `${__dirname}/src/vue`,
                '@scss': `${__dirname}/src/scss`,
                '@vc'  : `${__dirname}/src/vue/Components`
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
                                            options: {minimize: production}
                                        }, {
                                            loader : 'sass-loader',
                                            options: {minimize: production}
                                        }, {
                                            loader : 'sass-resources-loader',
                                            options: {resources: 'src/scss/Partials/_variables.scss'}
                                        }
                                    ],
                                    fallback: 'vue-style-loader'
                                }
                            )
                        }
                    }
                }, {
                    test: /\.scss$/,
                    use : ExtractTextPlugin.extract(
                        {
                            use: [
                                {
                                    loader : 'css-loader',
                                    options: {minimize: production}
                                }, {
                                    loader : 'sass-loader',
                                    options: {minimize: production}
                                }, {
                                    loader : 'sass-resources-loader',
                                    options: {resources: 'src/scss/Partials/_variables.scss'}
                                }
                            ]
                        }
                    )
                }, {
                    test   : /\.(png|jpg|gif|svg|eot|ttf|woff|woff2)$/,
                    loader : 'url-loader',
                    options: {
                        limit          : 2048,
                        outputPath     : 'css/',
                        publicPath     : './',
                        useRelativePath: false
                    }
                }
            ]
        },
        plugins
    };
};