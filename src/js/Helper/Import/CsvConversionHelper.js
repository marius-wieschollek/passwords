import API from '@js/Helper/api';
import LoggingService from "@js/Services/LoggingService";
import RandomColorService from '@js/Services/RandomColorService';
import LocalisationService from "@js/Services/LocalisationService";

export default class ImportCsvConversionHelper {

    /**
     * Parse a generic csv file
     *
     * @param data
     * @param options
     * @returns {{}}
     */
    static async processGenericCsv(data, options) {
        let profile      = options.profile === 'custom' ? options:this._getProfile(options.profile),
            {db, errors} = this._processCsv(data, profile),
            entries      = await this._convertCsv(db, profile);

        return {data: entries, errors};
    }

    /**
     * Parse a csv file from Passman
     *
     * @param data
     * @returns {Promise<*>}
     */
    static async processPassmanCsv(data) {
        let errors = [];

        for(let i = 1; i < data.length; i++) {
            let line = data[i], hasFiles = true;

            if(data[i][8].length > 2) {
                this._logConversionError('"{label}" has files attached which can not be imported.', {label: line[0]}, errors);
                hasFiles = true;
            }

            data[i][5] = line[5].substr(1, line[5].length - 2);
            data[i][7] = this._processPassmanCustomFields(line, errors, hasFiles);
        }

        let result = await this.processGenericCsv(data, {profile: 'passman'});
        result.errors = errors.concat(result.errors);

        return result;
    }

    /**
     *
     * @param line
     * @param errors
     * @param hasFiles
     * @returns {string}
     * @private
     */
    static _processPassmanCustomFields(line, errors, hasFiles = false) {
        let rawFields    = JSON.parse(line[7]),
            customFields = [];

        if(line[3] !== '') {
            customFields.push(LocalisationService.translate('Email') + ',email:' + line[3]);
        }

        for(let i = 0; i < rawFields.length; i++) {
            let field = rawFields[i],
                type  = field.field_type === 'password' ? 'secret':'text';

            if(field.field_type === 'file') {
                if(!hasFiles) this._logConversionError('"{label}" has files attached which can not be imported.', {label: line[0]}, errors);
                hasFiles = true;
                continue;
            }

            customFields.push(`${field.label},${type}:${field.value}`);
        }

        return customFields.join("\n");
    }

    /**
     *
     * @param data
     * @param options
     * @returns {{db: Array, errors: Array}}
     * @private
     */
    static _processCsv(data, options) {
        let mapping   = options.mapping,
            fieldMap  = {tagIds: 'tags', folderId: 'folder', parentId: 'parent'},
            errors    = [],
            db        = [],
            firstLine = Number.parseInt(0 + options.firstLine);

        if(!Array.isArray(data) || data.length < firstLine + 1) throw new Error('Import file is empty');
        if(!mapping || data[firstLine].length < mapping.length) throw new Error('CSV file can not be mapped');

        for(let i = firstLine; i < data.length; i++) {
            let line   = data[i],
                object = {};

            for(let j = 0; j < mapping.length; j++) {
                let field = mapping[j];

                if(field && field.length !== 0) {
                    let value = line[j];

                    if(value === undefined) continue;
                    let targetField = fieldMap.hasOwnProperty(field) ? fieldMap[field]:field;
                    object[targetField] = this._processCsvValue(value, field, errors);
                }
            }

            if(options.repair) this._repairObject(object);
            db.push(object);
        }

        return {db, errors};
    }

    /**
     *
     * @param value
     * @param field
     * @param errors
     * @returns {*}
     * @private
     */
    static _processCsvValue(value, field, errors) {
        let boolYes = LocalisationService.translate('true'),
            boolNo  = LocalisationService.translate('false');

        if([boolYes, 'yes', 'true', '1'].indexOf(value) !== -1) {
            return true;
        } else if([boolNo, 'no', 'false', '0'].indexOf(value) !== -1) {
            return false;
        } else if(field === 'edited') {
            if(!isNaN(value)) {
                if(value.length === 10) return new Date(parseInt(value) * 1000);
                if(value.length === 13) return new Date(parseInt(value));
            }

            return new Date(value);
        } else if((field === 'tags' || field === 'tagLabels') && value.length !== 0 && !Array.isArray(value)) {
            return value.split(',');
        } else if(field === 'customFields') {
            return this._processCustomFields(value, errors);
        }
        return value;
    }

    /**
     *
     * @param data
     * @param errors
     * @returns {Array}
     * @private
     */
    static _processCustomFields(data, errors) {
        let rawFields    = data.split("\n"),
            customFields = [];

        for(let i = 0; i < rawFields.length; i++) {
            if(rawFields[i].trim() === '') continue;
            let label = '',
                value = rawFields[i],
                type  = 'text';

            if(value.indexOf(':') !== -1) {
                label = value.substr(0, value.indexOf(':'));
                value = value.substr(value.indexOf(':') + 1);
            }

            value = value.trim();
            label = label.trim();
            if(label.indexOf(',') !== -1) {
                type = label.substr(label.indexOf(',') + 1);
                label = label.substr(0, label.indexOf(','));

                label = label.trim();
                type = type.trim();
                if(type === 'password') type = 'secret';
                if((type === 'url' && (!value.match(/^\w+:\/\/.+$/) || value.substr(0, 11) === 'javascript:')) ||
                   ['text', 'email', 'url', 'file', 'secret', 'data'].indexOf(type) === -1 ||
                   (type === 'email' && !value.match(/^[\w._-]+@.+$/))
                ) {
                    this._logConversionError('The value of "{field}" did not have the type {type} and was changed to text.', {field: label, type, value}, errors);
                    type = 'text';
                }
            }

            if(label.length < 1) label = type.capitalize();
            if(label.length > 48) {
                this._logConversionError('The label of "{field}" exceeds 48 characters and was cut.', {field: label}, errors);
                label = label.substr(0, 48);
            }
            if(value.length > 320) {
                this._logConversionError('The value of "{field}" exceeds 320 characters and was cut.', {field: label}, errors);
                value = value.substr(0, 320);
            }

            if(value.match(/^[\w._-]+@.+$/)) {
                type = 'email';
            } else if(value.match(/^\w+:\/\/.+$/) && value.substr(0, 11) !== 'javascript:') {
                type = 'url';
            }

            customFields.push({label, type, value: value.trim()});
        }

        return customFields;
    }

    /**
     *
     * @param data
     * @param options
     * @returns {Promise<{tags: *, folders: *, passwords: {}}>}
     * @private
     */
    static async _convertCsv(data, options) {
        let [tags, tagIdMap, tagKeyMap]          = await this._generateTags(data, options),
            [folders, folderIdMap, folderKeyMap] = await this._generateFolders(data, options),
            db                                   = {tags, folders, passwords: []};

        if(options.db === 'passwords') {
            let passwordIdMap = this._createLabelMapping(await API.listPasswords());
            this._setIds(data, db.passwords, passwordIdMap);
        } else if(options.db === 'folders') {
            this._setIds(data, db.folders, folderIdMap, folderKeyMap);
        } else if(options.db === 'tags') {
            this._setIds(data, db.tags, tagIdMap, tagKeyMap);
        }

        return db;
    }

    /**
     *
     * @param data
     * @param db
     * @param idMap
     * @param keyMap
     * @private
     */
    static _setIds(data, db = [], idMap = {}, keyMap = {}) {
        for(let i = 0; i < data.length; i++) {
            let element = data[i], label = element.label;

            if(idMap.hasOwnProperty(label)) element.id = idMap[label];
            if(keyMap.hasOwnProperty(label)) {
                db[keyMap[label]] = element;
            } else {
                db.push(element);
            }
        }
    }

    /**
     *
     * @param db
     * @param options
     * @returns {Promise<Array>}
     * @private
     */
    static async _generateTags(db, options) {
        if(options.mapping.indexOf('tagLabels') === -1) return [[], {}, {}];
        let tags   = [],
            keyMap = {},
            idMap  = this._createLabelMapping(await API.listTags());

        for(let i = 0; i < db.length; i++) {
            let element = db[i];

            if(!element.hasOwnProperty('tagLabels')) continue;
            if(!element.hasOwnProperty('tags')) element.tags = [];

            for(let j = 0; j < element.tagLabels.length; j++) {
                let label = element.tagLabels[j];

                if(!idMap.hasOwnProperty(label)) {
                    keyMap[label] = tags.length;
                    tags.push({id: label, label, color: RandomColorService.color()});
                    element.tags[j] = label;
                    idMap[label] = label;
                } else {
                    element.tags[j] = idMap[label];
                }
            }
        }

        return [tags, idMap, keyMap];
    }

    /**
     *
     * @param db
     * @param options
     * @returns {Promise<*[]>}
     * @private
     */
    static async _generateFolders(db, options) {
        if(options.mapping.indexOf('folderLabel') === -1 && options.mapping.indexOf('parentLabel') === -1) return [[], {}, {}];
        let folders    = [],
            keyMap     = {},
            properties = {folder: 'folderLabel', parent: 'parentLabel'},
            idMap      = this._createLabelMapping(await API.listFolders());
        idMap[''] = '00000000-0000-0000-0000-000000000000';

        for(let i = 0; i < db.length; i++) {
            let element = db[i];

            if((element.hasOwnProperty('folder') && idMap.hasOwnProperty(element.folder) &&
                element.hasOwnProperty('parent') && idMap.hasOwnProperty(element.parent)) ||
               (!element.hasOwnProperty('folderLabel') && !element.hasOwnProperty('parentLabel'))
            ) {
                continue;
            }

            for(let j in properties) {
                if(!properties.hasOwnProperty(j)) continue;
                if(element.hasOwnProperty(properties[j])) {
                    let label = element[properties[j]];
                    if(!idMap.hasOwnProperty(label)) {
                        keyMap[label] = folders.length;
                        folders.push({id: label, label});
                        element[j] = label;
                        idMap[label] = label;
                    } else {
                        element[j] = idMap[label];
                    }
                }
            }
        }

        return [folders, idMap, keyMap];
    }

    /**
     *
     * @param db
     * @returns {{}}
     * @private
     */
    static _createLabelMapping(db) {
        let map = {};

        for(let i in db) {
            if(!db.hasOwnProperty(i)) continue;
            let id = db[i].id;
            map[db[i].label] = id;
            map[id] = id;
        }

        return map;
    }

    /**
     * Can improve bad input data
     *
     * @param object
     */
    static _repairObject(object) {
        let domain = new RegExp('^([a-zA-Z0-9][a-zA-Z0-9-]{1,61}[a-zA-Z0-9]\\.){1,}[a-zA-Z]{2,}$');
        if(object.url && object.url.length !== 0 && object.url.indexOf('://') === -1) {
            object.url = `https://${object.url}`;
        } else if(object.url && (object.url === 'http://sn' || object.url === 'http://')) {
            // LastPass CSV specific fix
            object.url = null;
        } else if(!object.url || object.url.length === 0) {
            if(object.label && domain.test(object.label)) {
                object.url = API.parseUrl(object.label, 'href');
            }
        }

        if(!object.hasOwnProperty('password') || typeof object.password !== 'string' || object.password.length < 1) {
            object.password = 'password-missing-during-import';
        }
    }

    /**
     *
     * @param name
     * @returns {*}
     * @private
     */
    static _getProfile(name) {
        let profiles = {
            passwords   : {
                firstLine: 1,
                db       : 'passwords',
                mapping  : ['label', 'username', 'password', 'notes', 'url', 'customFields', 'folderLabel', 'tagLabels', 'favorite', 'edited', 'id', 'revision', 'folderId']
            },
            folders     : {
                firstLine: 1,
                db       : 'folders',
                mapping  : ['label', 'parentLabel', 'favorite', 'edited', 'id', 'revision', 'parentId']
            },
            tags        : {
                firstLine: 1,
                db       : 'tags',
                mapping  : ['label', 'color', 'favorite', 'edited', 'id', 'revision']
            },
            legacy      : {
                firstLine: 1,
                db       : 'passwords',
                mapping  : ['label', 'username', 'password', 'url', 'notes'],
                repair   : true
            },
            keepass     : {
                firstLine: 1,
                db       : 'passwords',
                mapping  : ['folderLabel', 'label', 'username', 'password', 'url', 'notes']
            },
            bitwardenCsv: {
                firstLine: 1,
                db       : 'passwords',
                mapping  : ['tagLabels', 'favorite', 'folderLabel', 'label', 'notes', 'customFields', '', 'url', 'username', 'password'],
                repair   : true
            },
            lastpass    : {
                firstLine: 1,
                db       : 'passwords',
                mapping  : ['url', 'username', 'password', '', 'notes', 'label', 'tagLabels', 'favorite'],
                repair   : true
            },
            passman     : {
                firstLine: 1,
                db       : 'passwords',
                mapping  : ['label', 'username', 'password', '', 'notes', 'tagLabels', 'url', 'customFields'],
                repair   : true
            },
            dashlane    : {
                firstLine: 1,
                db       : 'passwords',
                mapping  : ['label', 'url', 'username', 'password', 'notes'],
                repair   : true
            },
            roboform    : {
                firstLine: 1,
                db       : 'passwords',
                mapping  : ['label', 'url', '', 'username', 'password', 'notes']
            },
            safeincloud : {
                firstLine: 1,
                db       : 'passwords',
                mapping  : ['label', 'username', 'password', 'url', 'notes']
            },
            chrome      : {
                firstLine: 1,
                db       : 'passwords',
                mapping  : ['label', 'url', 'username', 'password']
            },
            firefox     : {
                firstLine: 1,
                db       : 'passwords',
                mapping  : ['url', 'username', 'password', 'notes', '', '', '', '', 'edited']
            }
        };

        return profiles[name];
    }

    /**
     *
     * @param text
     * @param vars
     * @param errors
     * @private
     */
    static _logConversionError(text, vars, errors) {
        let message = LocalisationService.translate(text, vars);
        errors.push(message);
        LoggingService.error(message, vars);
    }
}