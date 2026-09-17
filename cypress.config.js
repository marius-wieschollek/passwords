const {defineConfig} = require('cypress');
const {execFile} = require('node:child_process');
const {promisify} = require('node:util');
const {mkdir} = require('node:fs/promises');
const path = require('node:path');
const {cypressBrowserPermissionsPlugin} = require('cypress-browser-permissions');
const {Jimp} = require('jimp');

const execFileAsync = promisify(execFile);

module.exports = defineConfig(
    {
        e2e: {
            baseUrl: 'https://localhost',
            viewportHeight: 800,
            viewportWidth: 1280,
            setupNodeEvents(on, config) {
                let container = config.expose.phpContainer || 'passwords-php';

                config = cypressBrowserPermissionsPlugin(on, config);

                on('task', {
                    occ(args) {
                        return execFileAsync(
                            'docker',
                            ['exec', '-u', 'www-data', container, '/var/www/html/occ', '--no-ansi', ...args]
                        ).then(({stdout}) => stdout.trim());
                    },
                    async thumbnail(fileName) {
                        let source = path.join('cypress', 'screenshots', `${fileName}.png`),
                            target = path.join('cypress', 'screenshots', '_previews', `${fileName}.jpg`),
                            image = await Jimp.read(source);

                        await mkdir(path.dirname(target), {recursive: true});

                        if (image.width > 320 || image.height > 200) {
                            image.scaleToFit({w: 320, h: 200});
                        }

                        await image.write(target, {quality: 90});

                        return null;
                    }
                });

                return config;
            }
        },
        expose: {
            phpContainer: 'passwords-php'
        }
    }
);