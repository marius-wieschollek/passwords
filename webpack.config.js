let webpack                    = require('webpack'),
    config                     = require('./package.json'),
    UglifyJS                   = require('uglify-js'),
    CopyWebpackPlugin          = require('copy-webpack-plugin'),
    CleanWebpackPlugin         = require('clean-webpack-plugin'),
    VueLoaderPlugin            = require('vue-loader/lib/plugin'),
    MiniCssExtractPlugin       = require('mini-css-extract-plugin'),
    CssMinimizerPlugin         = require('css-minimizer-webpack-plugin'),
    CompileLanguageFilesPlugin = require("./scripts/compile-language-files.js");

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
                    APP_NIGHTLY        : !!(env && env.features === 'true')
                }
            ),
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

    return {
        mode        : production ? 'production':'development',
        entry       : {
            app  : `${__dirname}/src/js/app.js`,
            admin: `${__dirname}/src/js/admin.js`
        },
        output      : {
            path         : `${__dirname}/src/`,
            filename     : 'js/Static/[name].js',
            chunkFilename: 'js/Static/[name].[chunkhash].js'
        },
        optimization: {
            minimize : production,
            minimizer: [
                `...`,
                new CssMinimizerPlugin(
                    {
                        parallel: true
                    }
                )
            ]
        },
        resolve     : {
            modules   : ['node_modules', 'src'],
            extensions: ['.js', '.vue', '.scss'],
            fallback  : {
                path  : false,
                crypto: false
            },
            alias     : {
                'vue$' : 'vue/dist/vue.esm.js',
                '@'    : `${__dirname}/src`,
                '@js'  : `${__dirname}/src/js`,
                '@vue' : `${__dirname}/src/vue`,
                '@scss': `${__dirname}/src/scss`,
                '@vc'  : `${__dirname}/src/vue/Components`
            }
        },
        module      : {
            rules: [
                {
                    test  : /\.vue$/,
                    loader: 'vue-loader'
                },
                {
                    test: /\.scss$/,
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