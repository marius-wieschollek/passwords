import API from '@js/Helper/api';
import SimpleApi from '@js/ApiClient/SimpleApi';
import * as randomMC from 'random-material-color';
import Localisation from '@js/Classes/Localisation';

export default class ImportCsvConversionHelper {

    /**
     * Parse a generic csv file
     *
     * @param data
     * @param options
     * @returns {{}}
     */
    static async processGenericCsv(data, options) {
        let profile = options.profile === 'custom' ? options:this._getProfile(options.profile),
            entries = this._processCsv(data, profile);

        return await this._convertCsv(entries, profile);
    }

    /**
     * Parse a csv file from Passman
     *
     * @param data
     * @returns {Promise<*>}
     */
    static async processPassmanCsv(data) {

        for(let i = 0; i < data.length; i++) {
            let line = data[i];

            data[i][5] = line[5].substr(1, line[5].length - 2);
        }

        let profile = this._getProfile('passman'),
            entries = this._processCsv(data, profile);
        return await this._convertCsv(entries, profile);
    }

    /**
     *
     * @param data
     * @param options
     * @returns {Promise<{}>}
     * @private
     */
    static _processCsv(data, options) {
        let mapping   = options.mapping,
            fieldMap  = {tagIds: 'tags', folderId: 'folder', parentId: 'parent'},
            db        = [],
            firstLine = Number.parseInt(0 + options.firstLine);

        if(data[firstLine].length < mapping.length) throw new Error('CSV file can not be mapped');
        for(let i = firstLine; i < data.length; i++) {
            let line   = data[i],
                object = {};

            for(let j = 0; j < mapping.length; j++) {
                let field = mapping[j];

                if(field && field.length !== 0) {
                    let value = line[j];

                    if(value === undefined) continue;
                    let targetField = fieldMap.hasOwnProperty(field) ? fieldMap[field]:field;
                    object[targetField] = this._processCsvValue(value, field);
                }
            }

            if(options.repair) this._repairObject(object);
            db.push(object);
        }

        return db;
    }

    /**
     *
     * @param value
     * @param field
     * @returns {*}
     * @private
     */
    static _processCsvValue(value, field) {
        let boolYes = Localisation.translate('true'),
            boolNo  = Localisation.translate('false');

        if([boolYes, 'yes', 'true', '1'].indexOf(value) !== -1) {
            return true;
        } else if([boolNo, 'no', 'false', '0'].indexOf(value) !== -1) {
            return false;
        } else if(field === 'edited') {
            return new Date(value);
        } else if((field === 'tags' || field === 'tagLabels') && value.length !== 0 && !Array.isArray(value)) {
            return value.split(',');
        } else if(field === 'customFields') {
            return this._processCustomFields(value);
        }
        return value;
    }

    /**
     *
     * @param value
     * @returns {Array}
     * @private
     */
    static _processCustomFields(value) {
        let rawFields    = value.split("\n"),
            customFields = [];

        for(let i = 0; i < rawFields.length; i++) {
            if(rawFields[i].trim() === '') continue;
            let [label, content] = rawFields[i].split(':', 2),
                type             = 'text';

            label = label.trim();
            if(label.indexOf(',') !== -1) {
                [label, type] = label.split(',', 2);

                type = type.trim();
                if(['text', 'email', 'url', 'file', 'secret', 'data'].indexOf(type) === -1) {
                    type = 'text';
                } else if(type === 'email' && !content.match(/^[\w._-]+@.+$/)) {
                    type = 'text';
                } else if(type === 'url' && (!content.match(/^\w+:\/\/.+$/) || content.substr(0, 11) === 'javascript:')) {
                    type = 'text';
                }
            }

            if(label.length < 1) label = type.capitalize();
            if(label.length > 48) label = label.substr(0, 48);
            if(content.length > 320) content = content.substr(0, 320);

            if(content.match(/^[\w._-]+@.+$/)) {
                type = 'email';
            } else if(content.match(/^\w+:\/\/.+$/) && content.substr(0, 11) !== 'javascript:') {
                type = 'url';
            }

            customFields.push({label, type, value: content.trim()});
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
                    tags.push({id: label, label, color: randomMC.getColor()});
                    element.tags[j] = idMap[label];
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
        } else if(!object.url || object.url.length === 0) {
            if(object.label && domain.test(object.label)) {
                object.url = SimpleApi.parseUrl(object.label, 'href');
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
            passwords: {
                firstLine: 1,
                db       : 'passwords',
                mapping  : ['label', 'username', 'password', 'notes', 'url', 'customFields', 'folderLabel', 'tagLabels', 'favorite', 'edited', 'id', 'revision', 'folderId']
            },
            folders  : {
                firstLine: 1,
                db       : 'folders',
                mapping  : ['label', 'parentLabel', 'favorite', 'edited', 'id', 'revision', 'parentId']
            },
            tags     : {
                firstLine: 1,
                db       : 'tags',
                mapping  : ['label', 'color', 'favorite', 'edited', 'id', 'revision']
            },
            legacy   : {
                firstLine: 1,
                db       : 'passwords',
                mapping  : ['label', 'username', 'password', 'url', 'notes'],
                repair   : true
            },
            keepass  : {
                firstLine: 1,
                db       : 'passwords',
                mapping  : ['label', 'username', 'password', 'url', 'notes']
            },
            lastpass : {
                firstLine: 1,
                db       : 'passwords',
                mapping  : ['url', 'username', 'password', 'notes', 'label', 'folderLabel', 'favorite']
            },
            passman  : {
                firstLine: 1,
                db       : 'passwords',
                mapping  : ['label', 'username', 'password', '', 'notes', 'tagLabels', 'url'],
                repair   : true
            },
            dashlane : {
                firstLine: 1,
                db       : 'passwords',
                mapping  : ['label', 'url', 'username', 'password', 'notes'],
                repair   : true
            }
        };

        return profiles[name];
    }
}