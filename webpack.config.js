let webpack            = require('webpack'),
    config             = require('./package.json'),
    UglifyJS           = require('uglify-es'),
    UglifyJSPlugin     = require('uglifyjs-webpack-plugin'),
    CopyWebpackPlugin  = require('copy-webpack-plugin'),
    CleanWebpackPlugin = require('clean-webpack-plugin'),
    ExtractTextPlugin  = require('extract-text-webpack-plugin'),
    ProgressBarPlugin  = require('progress-bar-webpack-plugin'),
    OptimizeCSSPlugin  = require('optimize-css-assets-webpack-plugin');

module.exports = (env) => {
    let production = !!(env && env.production);
    console.log('Production: ', production);

    let transform = (content) => {
            let result = UglifyJS.minify(content.toString('utf8'));
            if(result.error) console.error(result.error);
            if(result.warnings) console.warn(result.warnings);
            return result.code;
        },
        plugins   = [
            new webpack.DefinePlugin(
                {
                    'process.env': {
                        NODE_ENV        : production ? '"production"':'"development"',
                        APP_VERSION     : `"${config.version}"`,
                        APP_NAME        : '"webapp"',
                        NIGHTLY_FEATURES: !!(env && env.features)
                    }
                }
            ),
            new ExtractTextPlugin({filename: 'css/[name].css', allChunks: true}),
            new CopyWebpackPlugin(
                [
                    {from: `${__dirname}/src/js/Helper/utility.js`, to: `${__dirname}/src/js/Static/utility.js`, transform},
                    {from: `${__dirname}/src/js/Helper/https-debug.js`, to: `${__dirname}/src/js/Static/https-debug.js`, transform},
                    {from: `${__dirname}/src/js/Helper/compatibility.js`, to: `${__dirname}/src/js/Static/compatibility.js`, transform}
                ]
            ),
            new webpack.optimize.CommonsChunkPlugin({name: 'common', minChunks: Infinity}),
            new CleanWebpackPlugin(['src/css', 'src/js/Static'])
        ];

    if(production) {
        let transformJson = (content) => {
            let data = JSON.parse(content.toString('utf8'));
            return JSON.stringify(data);
        };

        plugins.push(new OptimizeCSSPlugin({cssProcessorOptions: {safe: true}}));
        plugins.push(
            new UglifyJSPlugin(
                {
                    uglifyOptions: {
                        beautify: false,
                        ecma    : 8,
                        compress: true,
                        comments: false,
                        ascii   : true
                    },
                    cache        : true,
                    parallel     : true
                }
            )
        );
        if(!!(env && env.compress)) {
            plugins.push(
                new CopyWebpackPlugin(
                    [
                        {from: `${__dirname}/src/l10n/*.js`, to: `${__dirname}/`, transform},
                        {from: `${__dirname}/src/l10n/*.json`, to: `${__dirname}/`, transform: transformJson},
                        {from: `${__dirname}/src/l10n/*/*.json`, to: `${__dirname}/`, transform: transformJson}
                    ]
                ));
        }
        plugins.push(new ProgressBarPlugin());
    }

    return {
        entry  : {
            app  : `${__dirname}/src/js/app.js`,
            admin: `${__dirname}/src/js/admin.js`
        },
        output : {
            path         : `${__dirname}/src/`,
            filename     : 'js/Static/[name].js',
            chunkFilename: 'js/Static/[name].[hash].js',
            jsonpFunction: 'passwordsWebpackJsonp'
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
                        limit          : 1024,
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