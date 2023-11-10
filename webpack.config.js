let webpack                    = require('webpack'),
    config                     = require('./package.json'),
    UglifyJS                   = require('uglify-js'),
    {exec}                     = require("child_process"),
    CopyWebpackPlugin          = require('copy-webpack-plugin'),
    CleanWebpackPlugin         = require('clean-webpack-plugin'),
    VueLoaderPlugin            = require('vue-loader/lib/plugin'),
    MiniCssExtractPlugin       = require('mini-css-extract-plugin'),
    CssMinimizerPlugin         = require('css-minimizer-webpack-plugin'),
    CompileLanguageFilesPlugin = require('./scripts/compile-language-files.js'),
    BundleAnalyzerPlugin       = require('webpack-bundle-analyzer').BundleAnalyzerPlugin;

module.exports = (env, argv) => {
    let production = argv.mode === 'production';
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
                    APP_TYPE           : '"webapp"',
                    APP_VERSION        : `"${config.version}"`,
                    APP_MAIN_VERSION   : `"${config.version.substr(0, config.version.indexOf('.'))}"`,
                    APP_FEATURE_VERSION: `"${config.version.substr(0, config.version.lastIndexOf('.'))}"`,
                    APP_ENVIRONMENT    : production ? '"production"':'"development"',
                    APP_NIGHTLY        : !!(env && env.features === 'true'),
                    appName            : 'passwords',
                    appVersion         : `"${config.version}"`
                }
            ),
            new webpack.ProvidePlugin({process: 'process/browser.js'}),
            new CopyWebpackPlugin(
                {
                    patterns: [
                        {from: `${__dirname}/src/js/Helper/utility.js`, to: `${__dirname}/src/js/Static/utility.js`, transform},
                        {from: `${__dirname}/src/js/Helper/https-debug.js`, to: `${__dirname}/src/js/Static/https-debug.js`, transform},
                        {from: `${__dirname}/src/js/Helper/compatibility.js`, to: `${__dirname}/src/js/Static/compatibility.js`, transform}
                    ]
                }
            ),
            new VueLoaderPlugin(),
            new MiniCssExtractPlugin({filename: 'css/[name].css', chunkFilename: 'css/[name].[chunkhash].css'}),
            new CleanWebpackPlugin([`${__dirname}/src/css`, `${__dirname}/src/js/Static`]),
            new CompileLanguageFilesPlugin()
        ];

    if(!!(env && env.debuginfo === 'true')) {
        plugins.push(
            new BundleAnalyzerPlugin(
                {
                    analyzerMode     : 'static',
                    reportFilename   : 'bundle-report.html',
                    openAnalyzer     : false,
                    generateStatsFile: true,
                    statsFilename    : 'report-stats.json'
                }
            )
        );
    }
    if(!production || !!(env && env.debuginfo === 'true')) {
        class MetaInfoPlugin {
            apply(compiler) {
                compiler.hooks.done.tap(this.constructor.name, stats => {
                    exec(
                        'docker exec -u www-data passwords-php php ./occ config:app:set passwords dev/app/hash --value=' + stats.compilation.hash,
                        (error, stdout, stderr) => {
                            if(error) {
                                console.error(`Could not set app hash: ${error}`);
                            }
                        }
                    );
                });
            }
        }

        plugins.push(new MetaInfoPlugin());
    }

    return {
        mode        : production ? 'production':'development',
        devtool     : false,
        entry       : {
            app      : `${__dirname}/src/js/app.js`,
            admin    : `${__dirname}/src/js/admin.js`,
            dashboard: `${__dirname}/src/js/dashboard.js`
        },
        output      : {
            path         : `${__dirname}/src/`,
            filename     : (!production || !!(env && env.debuginfo === 'true')) ? `js/Static/[name].[fullhash].js`:`js/Static/[name].js`,
            chunkFilename: 'js/Static/[name].[chunkhash].js'
        },
        optimization: {
            minimize   : production,
            minimizer  : [
                `...`,
                new CssMinimizerPlugin(
                    {
                        parallel: true
                    }
                )
            ],
            usedExports: true
        },
        resolve     : {
            modules   : ['node_modules', 'src'],
            extensions: ['.js', '.vue', '.scss'],
            fallback  : {
                path  : false,
                crypto: false,
                assert: false,
                util  : false
            },
            alias     : {
                'vue$' : 'vue/dist/vue.esm.js',
                '@'    : `${__dirname}/src`,
                '@js'  : `${__dirname}/src/js`,
                '@vue' : `${__dirname}/src/vue`,
                '@scss': `${__dirname}/src/scss`,
                '@vc'  : `${__dirname}/src/vue/Components`,
                '@icon': `${__dirname}/node_modules/vue-material-design-icons`,
                '@nc'  : `@nextcloud/vue/dist/Components`
            }
        },
        module      : {
            rules: [
                {
                    test  : /\.vue$/,
                    loader: 'vue-loader'
                },
                {
                    test: /\.s?css$/,
                    use : [
                        {loader: 'vue-style-loader'},
                        {
                            loader : MiniCssExtractPlugin.loader,
                            options: {
                                esModule: true
                            }
                        },
                        {
                            loader : 'css-loader',
                            options: {
                                esModule: true,
                                modules : 'global',
                                url     : {
                                    filter: (url) => {
                                        return url.indexOf('/apps/passwords/') === -1;
                                    }
                                }
                            }
                        },
                        {
                            loader : 'sass-loader',
                            options: {
                                sassOptions: {
                                    sourceMap  : !production,
                                    outputStyle: production ? 'compressed':null
                                }
                            }
                        },
                        {
                            loader : 'sass-resources-loader',
                            options: {
                                resources: [
                                    `${__dirname}/src/scss/Partials/_variables.scss`
                                ]
                            }
                        }
                    ]
                },
                {
                    test     : /\.(png|jpg|gif|svg|eot|ttf|woff|woff2)$/,
                    type     : 'asset',
                    generator: {
                        filename: 'css/[contenthash][ext][query]'
                    }
                }
            ]
        },
        plugins
    };
};