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

    get ALIASES() {
        return {
            "backend-000652": [{section: 'settings'}],
            "backend-000653": [{section: 'settings'}],
            "backend-000654": [{section: 'settings'}],
            "backend-000655": [{section: 'settings'}],
            "backend-000656": [{section: 'settings'}]
        };
    }

    get FORCE_KEYS() {
        return this._keys;
    }

    constructor(options = {}) {
        if(!options.hasOwnProperty('sourcePath')) {
            options.sourcePath = '.weblate';
        }
        if(!options.hasOwnProperty('targetPath')) {
            options.targetPath = 'src/l10n';
        }
        if(!options.hasOwnProperty('defaultLanguage')) {
            options.defaultLanguage = 'en';
        }

        this._options = options;
        this._initialized = false;
        this._mtimes = {};
        this._index = {};
        this._keys = {};
        this._isWorking = false;
    }

    apply(compiler) {
        compiler.hooks.compilation.tap('CompileLanguageFilesPlugin', (compilation) => {
            this._processLanguageFiles()
                .catch(console.error);
        });
    }

    /**
     * Process all modified language files
     *
     * @returns {Promise<void>}
     * @private
     */
    async _processLanguageFiles() {
        if(this._isWorking) {
            return;
        }

        this._isWorking = true;
        if(!this._initialized) {
            if(fsO.existsSync(this._options.targetPath)) {
                await fs.rm(this._options.targetPath, {recursive: true, force: true});
            }
            await fs.mkdir(this._options.targetPath);

            await this._readKeysFile();
            await this._processDefaultLanguageFile();
            this._initialized = true;
        } else {
            await this._readKeysFile();
            await this._processDefaultLanguageFile();
        }

        let files = await fs.readdir(this._options.sourcePath, {withFileTypes: true});
        for(let file of files) {
            if(file.isDirectory()) {
                let path = `${this._options.sourcePath}/${file.name}/messages.json`;
                let stats = await fs.stat(path);
                if(!this._mtimes.hasOwnProperty(file.name) || this._mtimes[file.name] !== stats.mtime) {
                    await this._processLanguageFile(path, file.name).catch(console.error);

                    this._mtimes[file.name] = stats.mtime;
                }
            }
        }

        this._isWorking = false;
    }

    /**
     * Processes a single language file
     *
     * @param {String} file
     * @param language
     * @returns {Promise<void>}
     * @private
     */
    async _processLanguageFile(file, language) {
        let languageKeys = JSON.parse(await readFile(file, {encoding: 'utf-8'})),
            translations = this._processLanguageKeys(languageKeys);
        await this._writeSectionL10nFiles(translations, language);
    }

    /**
     *
     * @param languageKeys
     * @returns {Object}
     * @private
     */
    _processLanguageKeys(languageKeys) {
        let translations = {};

        for(let indexKey in this._index) {
            if(!languageKeys.hasOwnProperty(indexKey)) {
                languageKeys[indexKey] = this._index[indexKey];
            }
        }

        for(let key in languageKeys) {
            if(!languageKeys.hasOwnProperty(key)) {
                continue;
            }
            if(!this._index.hasOwnProperty(key)) {
                console.warn(`Unknown translation key ${key}`);
                continue;
            }
            let value      = this._processEntry(languageKeys[key]),
                baseString = key.substring(key.indexOf('-') + 1),
                section    = 'frontend';

            if(this.FORCE_KEYS.hasOwnProperty(key)) {
                baseString = this.FORCE_KEYS[key];
            }

            if(value === baseString) {
                continue;
            }

            if(key.indexOf('-') !== -1) {
                section = key.substring(0, key.indexOf('-'));
            }

            if(!translations.hasOwnProperty(section)) {
                translations[section] = {};
            }

            translations[section][baseString] = value;

            if(this.ALIASES.hasOwnProperty(key)) {
                for(let alias of this.ALIASES[key]) {
                    if(!translations.hasOwnProperty(alias.section)) {
                        translations[alias.section] = {};
                    }

                    if(alias.hasOwnProperty('name')) {
                        translations[alias.section][alias.name] = value;
                    } else {
                        translations[alias.section][baseString] = value;
                    }
                }
            }
        }
        return translations;
    }

    /**
     * Write the translations to the files in the l10n folder
     *
     * @param {Object} translations
     * @param {String} language
     * @returns {Promise<void>}
     * @private
     */
    async _writeSectionL10nFiles(translations, language) {
        for(let section in translations) {
            if(!translations.hasOwnProperty(section)) {
                continue;
            }

            let path = this._options.targetPath;
            if(section !== 'backend' && section !== 'frontend') {
                path = `${this._options.targetPath}/${section}`;
                if(!fsO.existsSync(path)) {
                    await fs.mkdir(path);
                }
            }

            let content = '';
            if(section !== 'frontend') {
                content = JSON.stringify(
                    {
                        translations: translations[section],
                        pluralForm  : 'nplurals=2; plural=(n != 1);'
                    }
                );
                path += `/${language}.json`;
            } else {
                content = `(function(){OC.L10N.register("passwords",${JSON.stringify(translations[section])},"nplurals=2; plural=(n != 1);")}())`;
                path += `/${language}.js`;
            }

            let buffer = new Uint8Array(Buffer.from(content));
            await writeFile(path, buffer);
        }
    }

    /**
     * Fill the index with the keys from the file of the default language
     *
     * @returns {Promise<void>}
     * @private
     */
    async _processDefaultLanguageFile() {
        let path  = `${this._options.sourcePath}/${this._options.defaultLanguage}/messages.json`,
            stats = await fs.stat(path);

        if(this._mtimes.hasOwnProperty(this._options.defaultLanguage) && this._mtimes[this._options.defaultLanguage] === stats.mtime) {
            return;
        }
        this._mtimes[this._options.defaultLanguage] = stats.mtime;

        this._index = {};
        let content = JSON.parse(await readFile(path, {encoding: 'utf-8'}));
        for(let key in content) {
            if(content.hasOwnProperty(key)) {
                this._index[key] = content[key];
            }
        }
    }

    async _readKeysFile() {
        let path  = `${this._options.sourcePath}/keys.json`,
            stats = await fs.stat(path);

        if(this._mtimes.hasOwnProperty('keys') && this._mtimes['keys'] === stats.mtime) {
            return;
        }
        this._mtimes['keys'] = stats.mtime;

        this._index = {};
        this._keys = JSON.parse(await readFile(path, {encoding: 'utf-8'}));
    }

    /**
     * Convert a message string to the NC format
     *
     * @param {Object} entry
     * @returns {String}
     * @private
     */
    _processEntry(entry) {
        if(entry.hasOwnProperty('placeholders')) {
            let message = entry.message;

            for(let placeholder in entry.placeholders) {
                if(!entry.placeholders.hasOwnProperty(placeholder)) {
                    continue;
                }

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