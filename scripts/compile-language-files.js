/*
 * @copyright 2022 Passwords App
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the Passwords App
 * created by Marius David Wieschollek.
 */

const fs = require('fs/promises');
const fsO = require('fs');
const {readFile, writeFile} = require("fs/promises");

class CompileLanguageFilesPlugin {
    constructor(options = {}) {
        if(!options.hasOwnProperty('sourcePath')) {
            options.sourcePath = '.weblate';
        }
        if(!options.hasOwnProperty('targetPath')) {
            options.targetPath = 'src/l10n';
        }

        this._options = options;
        this._initialized = false;
        this._mtimes = {};
        this._index = {};
        this._isWorking = false;
    }

    apply(compiler) {
        compiler.hooks.compilation.tap('CompileLanguageFilesPlugin', (compilation) => {
            this._processLanguageFiles()
                .catch(console.error);
        });
    }

    async _processLanguageFiles() {
        if(this._isWorking) {
            return;
        }

        this._isWorking = true;
        if(!this._initialized) {
            if(fsO.existsSync(this._options.targetPath)) {
                await fs.rmdir(this._options.targetPath);
            }
            await fs.mkdir(this._options.targetPath);

            await this._processDefaultLanguageFile();
            this._initialized = true;
        } else {
            await this._processDefaultLanguageFile();
        }

        let files     = await fs.readdir(this._options.sourcePath, {withFileTypes: true}),
            processes = [];
        for(let file of files) {
            if(file.isFile()) {
                let path = `${this._options.sourcePath}/${file.name}`;
                let stats = await fs.stat(path);
                if(!this._mtimes.hasOwnProperty(file.name) || this._mtimes[file.name] !== stats.mtime) {
                    processes.push(this._processLanguageFile(path).catch(console.error));

                    this._mtimes[file.name] = stats.mtime;
                }
            }
        }

        await Promise.all(processes);
        this._isWorking = false;
    }

    async _processLanguageFile(file) {
        let language     = file.substr(file.lastIndexOf('/') + 1).replace('.json', ''),
            languageKeys = JSON.parse(await readFile(file, {encoding: 'utf-8'})),
            translations = {};

        for(let key of languageKeys) {
            if(!this._index.hasOwnProperty(key)) {
                console.error('Unknown translation key ' + key);
                continue;
            }
            let value      = this._processEntry(languageKeys[key]),
                baseString = this._index[key],
                section    = 'frontend';

            if(value === baseString) {
                continue;
            }

            if(key.indexOf('-') !== -1) {
                section = key.substr(0, key.indexOf('-'));
            }

            if(!translations.hasOwnProperty(section)) {
                translations[section] = {};
            }

            translations[section][baseString] = value;
        }

        for(let section of translations) {
            let path = this._options.targetPath;
            if(section !== 'backend' && section !== 'frontend') {
                path = `${this._options.targetPath}/${section}`;
                await fs.mkdir(path);
            }

            let content = '';
            if(section !== 'backend') {
                content = JSON.stringify(
                    {
                        translations: translations[section],
                        pluralForm  : 'nplurals=2; plural=(n != 1);'
                    }
                );
                path += `${language}.json`;
            } else {
                content = `(function(){OC.L10N.register("passwords",${JSON.stringify(translations[section])},"nplurals=2; plural=(n != 1);")}())`;
                path += `${language}.js`;
            }

            let buffer = new Uint8Array(Buffer.from(content));
            await writeFile(path, buffer);
        }
    }

    async _processDefaultLanguageFile() {
        let path  = `${this._options.sourcePath}/en.json`,
            stats = await fs.stat(path);

        if(this._mtimes.hasOwnProperty('en.json') && this._mtimes['en.json'] === stats.mtime) {
            return;
        }
        this._mtimes['en.json'] = stats.mtime;

        this._index = {};
        let content = JSON.parse(await readFile(path, {encoding: 'utf-8'}));
        for(let key of content) {
            this._index[key] = this._processEntry(content[key]);
        }
    }

    _processEntry(entry) {
        if(entry.hasOwnProperty('placeholders')) {
            let message = entry.message;

            for(let placeholder of entry.placeholders) {
                let replacement;
                if(placeholder.substr(0, 4) === 'str_') {
                    replacement = `%${placeholder.substr(4)}$s`;
                } else if(placeholder.substr(0, 4) === 'num_') {
                    replacement = `%${placeholder.substr(4)}$d`;
                } else if(placeholder.substr(0, 7) === 'string_') {
                    replacement = `%${placeholder.substr(7)}$s`;
                } else if(placeholder.substr(0, 7) === 'number_') {
                    replacement = `%${placeholder.substr(7)}$s`;
                } else {
                    replacement = `{${placeholder}}`;
                }

                let key = `$${placeholder.toUpperCase()}$`;
                while(message.indexOf(key) !== -1) {
                    message = message.replace(key, replacement);
                }
            }

            return message;
        }

        return entry.message;
    }
}

module.exports = CompileLanguageFilesPlugin;