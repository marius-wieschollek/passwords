import API from "@js/Helper/api";
import Utility from "@js/Classes/Utility";
import SimpleApi from "@js/ApiClient/SimpleApi";
import * as randomMC from "random-material-color";

export default class ImportCsvConversionHelper {

    /**
     * Parse a generic csv file
     *
     * @param data
     * @param options
     * @returns {{}}
     */
    static async processGenericCsv(data, options) {
        let profile = options.profile === 'custom' ? options:ImportCsvConversionHelper._getGenericProfiles(options.profile);

        let entries = ImportCsvConversionHelper._processCsv(data, profile);
        return await ImportCsvConversionHelper._convertCsv(entries, profile);
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

        let profile = ImportCsvConversionHelper._getPassmanProfile(),
            entries = ImportCsvConversionHelper._processCsv(data, profile);
        return await ImportCsvConversionHelper._convertCsv(entries, profile);
    }

    /**
     *
     * @param data
     * @param options
     * @returns {Promise<{}>}
     * @private
     */
    static _processCsv(data, options) {
        let mapping  = options.mapping,
            fieldMap = {tagIds: 'tags', folderId: 'folder', parentId: 'parent'},
            db       = [],
            firstLine = Number.parseInt(0+options.firstLine);

        if(data[0].length < mapping.length) throw "CSV file can not be mapped";
        for(let i = firstLine; i < data.length; i++) {
            let line   = data[i],
                object = {};

            for(let j = 0; j < mapping.length; j++) {
                let field = mapping[j];

                if(field.length !== 0) {
                    let value = line[j];

                    if(value === undefined) continue;
                    let targetField = fieldMap.hasOwnProperty(field) ? fieldMap[field]:field;
                    object[targetField] = ImportCsvConversionHelper._processCsvValue(value, field);
                }
            }

            if(options.repair) ImportCsvConversionHelper._repairObject(object);
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
        let boolYes = Utility.translate('true'),
            boolNo  = Utility.translate('false');

        if([boolYes, 'yes', 'true', '1'].indexOf(value) !== -1) {
            return true;
        } else if([boolNo, 'no', 'false', '0'].indexOf(value) !== -1) {
            return false;
        } else if(field === 'edited') {
            return new Date(value);
        } else if((field === 'tags' || field === 'tagLabels') && value.length !== 0 && !Array.isArray(value)) {
            return value.split(',');
        }
        return value;
    }

    /**
     *
     * @param data
     * @param options
     * @returns {Promise<{tags: *, folders: *, passwords: {}}>}
     * @private
     */
    static async _convertCsv(data, options) {
        let [tags, tagIdMap, tagKeyMap]          = await ImportCsvConversionHelper._generateTags(data, options),
            [folders, folderIdMap, folderKeyMap] = await ImportCsvConversionHelper._generateFolders(data, options),
            db                                   = {tags: tags, folders: folders, passwords: []};

        if(options.db === 'passwords') {
            let passwordIdMap = ImportCsvConversionHelper._createLabelMapping(await API.listPasswords());
            ImportCsvConversionHelper._setIds(data, db.passwords, passwordIdMap);
        } else if(options.db === 'folders') {
            ImportCsvConversionHelper._setIds(data, db.folders, folderIdMap, folderKeyMap);
        } else if(options.db === 'tags') {
            ImportCsvConversionHelper._setIds(data, db.tags, tagIdMap, tagKeyMap);
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
        if(options.mapping.indexOf('tagLabels') === -1) return [[],{},{}];
        let tags   = [],
            keyMap = {},
            idMap  = ImportCsvConversionHelper._createLabelMapping(await API.listTags());

        for(let i = 0; i < db.length; i++) {
            let element = db[i];

            if(!element.hasOwnProperty('tagLabels')) continue;
            if(!element.hasOwnProperty('tags')) element.tags = [];

            for(let j = 0; j < element.tagLabels.length; j++) {
                let label = element.tagLabels[j];

                if(!idMap.hasOwnProperty(label)) {
                    keyMap[label] = tags.length;
                    tags.push({id: label, label: label, color: randomMC.getColor()});
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
        if(options.mapping.indexOf('folderLabel') === -1 && options.mapping.indexOf('parentLabel') === -1) return [[],{},{}];
        let folders    = [],
            keyMap     = {},
            properties = {folder: 'folderLabel', parent: 'parentLabel'},
            idMap      = ImportCsvConversionHelper._createLabelMapping(await API.listFolders());

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
                        folders.push({id: label, label: label});
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
        if(!object.url || object.url.length === 0) {
            if(object.label && object.label.match(/^([a-zA-Z0-9][a-zA-Z0-9-]{1,61}[a-zA-Z0-9]\.){1,}[a-zA-Z]{2,}$/)) {
                object.url = SimpleApi.parseUrl(object.label, 'href');
                object.label = null;
            }
        }
    }

    /**
     * Get generic Passman Profile
     *
     * @returns {{firstLine: number, db: string, mapping: string[]}}
     * @private
     */
    static _getPassmanProfile() {
        return {
            firstLine: 1,
            db       : 'passwords',
            mapping  : ['label', 'username', 'password', '', 'notes', 'tagLabels', 'url']
        };
    }

    /**
     *
     * @param name
     * @returns {*}
     * @private
     */
    static _getGenericProfiles(name) {
        let profiles = {
            passwords: {
                firstLine: 1,
                db       : 'passwords',
                delimiter: ',',
                mapping  : ['label', 'username', 'password', 'notes', 'url', 'folderLabel', 'tagLabels', 'favourite', 'edited', 'id', 'revision', 'folderId']
            },
            folders  : {
                firstLine: 1,
                db       : 'folders',
                delimiter: ',',
                mapping  : ['label', 'parentLabel', 'favourite', 'edited', 'id', 'revision', 'parentId']
            },
            tags     : {
                firstLine: 1,
                delimiter: ',',
                db       : 'tags',
                mapping  : ['label', 'color', 'favourite', 'edited', 'id', 'revision']
            },
            legacy   : {
                firstLine: 1,
                db       : 'passwords',
                delimiter: ',',
                mapping  : ['label', 'username', 'password', 'url', 'notes'],
                repair   : true
            },
            keepass   : {
                firstLine: 1,
                db       : 'passwords',
                delimiter: ',',
                mapping  : ['label', 'username', 'password', 'url', 'notes']
            },
            lastpass   : {
                firstLine: 1,
                db       : 'passwords',
                delimiter: ',',
                mapping  : ['url', 'username', 'password', 'notes', 'label', 'folderLabel', 'favourite']
            }
        };

        return profiles[name];
    }
}