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

    get FORCE_KEYS() {
        return {
            'CustomFields'   : 'Custom Fields',
            'FolderLabel'    : 'Folder',
            'TagLabels'      : 'Tags',
            'FolderId'       : 'Folder Id',
            'TagIds'         : 'Tag Ids',
            'frontend-000256': '{count} shares',
            'frontend-000277': 'Choose expiration date',
            'frontend-000353': 'CLIENT::MAINTENANCE',
            'frontend-000354': 'CLIENT::UNKNOWN',
            'frontend-000355': 'CLIENT::SYSTEM',
            'frontend-000356': 'CLIENT::PUBLIC',
            'frontend-000357': 'CLIENT::CRON',
            'frontend-000358': 'CLIENT::CLI'
        };
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

            await this._processDefaultLanguageFile();
            this._initialized = true;
        } else {
            await this._processDefaultLanguageFile();
        }

        let files = await fs.readdir(this._options.sourcePath, {withFileTypes: true});
        for(let file of files) {
            if(file.isFile()) {
                let path = `${this._options.sourcePath}/${file.name}`;
                let stats = await fs.stat(path);
                if(!this._mtimes.hasOwnProperty(file.name) || this._mtimes[file.name] !== stats.mtime) {
                    await this._processLanguageFile(path).catch(console.error);

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
     * @returns {Promise<void>}
     * @private
     */
    async _processLanguageFile(file) {
        let language     = file.substr(file.lastIndexOf('/') + 1).replace('.json', ''),
            languageKeys = JSON.parse(await readFile(file, {encoding: 'utf-8'})),
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
        for(let key in languageKeys) {
            if(!languageKeys.hasOwnProperty(key)) {
                continue;
            }
            if(!this._index.hasOwnProperty(key)) {
                console.warn(`Unknown translation key ${key}`);
                continue;
            }
            let value      = this._processEntry(languageKeys[key]),
                baseString = this._index[key],
                section    = 'frontend';

            if(this.FORCE_KEYS.hasOwnProperty(key)) {
                baseString = this.FORCE_KEYS[key];
            }

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
        let file  = `${this._options.defaultLanguage}.json`,
            path  = `${this._options.sourcePath}/${file}`,
            stats = await fs.stat(path);

        if(this._mtimes.hasOwnProperty(file) && this._mtimes[file] === stats.mtime) {
            return;
        }
        this._mtimes[file] = stats.mtime;

        this._index = {};
        let content = JSON.parse(await readFile(path, {encoding: 'utf-8'}));
        for(let key in content) {
            if(content.hasOwnProperty(key)) {
                this._index[key] = this._processEntry(content[key]);
            }
        }
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
                    replacement = `%s`;
                } else if(placeholder.substr(0, 4) === 'num_') {
                    replacement = `%d`;
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