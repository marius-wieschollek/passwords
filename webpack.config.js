let webpack              = require('webpack'),
    config               = require('./package.json'),
    UglifyJS             = require('uglify-es'),
    CopyWebpackPlugin    = require('copy-webpack-plugin'),
    {CleanWebpackPlugin} = require('clean-webpack-plugin'),
    VueLoaderPlugin      = require('vue-loader/lib/plugin'),
    MiniCssExtractPlugin = require('mini-css-extract-plugin'),
    OptimizeCSSPlugin    = require('optimize-css-assets-webpack-plugin');

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
            new CopyWebpackPlugin(
                [
                    {from: `${__dirname}/src/js/Helper/utility.js`, to: `${__dirname}/src/js/Static/utility.js`, transform},
                    {from: `${__dirname}/src/js/Helper/https-debug.js`, to: `${__dirname}/src/js/Static/https-debug.js`, transform},
                    {from: `${__dirname}/src/js/Helper/compatibility.js`, to: `${__dirname}/src/js/Static/compatibility.js`, transform}
                ]
            ),
            new VueLoaderPlugin(),
            new MiniCssExtractPlugin({filename: 'css/[name].css'}),
            new CleanWebpackPlugin(
                {
                    cleanOnceBeforeBuildPatterns: [`${__dirname}/src/css`, `${__dirname}/src/js/Static`]
                }
            )
        ];

    if(production) {
        let transformJson = (content) => {
            let data = JSON.parse(content.toString('utf8'));
            return JSON.stringify(data);
        };

        plugins.push(new OptimizeCSSPlugin({cssProcessorOptions: {safe: true}}));
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
            jsonpFunction: 'pwWpJsonP'
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
                            loader : MiniCssExtractPlugin.loader
                        },
                        {
                            loader: 'css-loader'
                        },
                        {
                            loader : 'sass-loader',
                            options: {outputStyle: 'compressed'}
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