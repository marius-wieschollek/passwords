import API from "@/js/Helper/api";
import Utility from "@/js/Classes/Utility";
import SimpleApi from "@/js/ApiClient/SimpleApi";
import * as randomMC from "random-material-color";

export default class ImportCsvConversionHelper {

    /**
     * Parse a generic csv file
     *
     * @param csv
     * @param options
     * @returns {{}}
     */
    static async processGenericCsv(csv, options) {
        let profile = options.profile === 'custom' ? options:ImportCsvConversionHelper._getGenericProfiles(options.profile),
            data    = Utility.parseCsv(csv, profile.delimiter);

        let d = ImportCsvConversionHelper._processCsv(data, profile);

        console.log(d);
        d = await ImportCsvConversionHelper._convertCsv(d, profile);
        console.log(d);
        return {};

        return d;
    }

    /**
     * Parse a csv file from Passman
     *
     * @param csv
     * @returns {Promise<*>}
     */
    static async processPassmanCsv(csv) {
        let data = Utility.parseCsv(csv, ',');

        for(let i = 0; i < data.length; i++) {
            let line = data[i];

            data[i][5] = line[5].substr(1, line[5].length - 2)
        }

        return ImportCsvConversionHelper._processCsv(data, ImportCsvConversionHelper._getPassmanProfile());
    }

    /**
     *
     * @param data
     * @param options
     * @returns {Promise<{}>}
     * @private
     */
    static _processCsv(data, options) {
        let mapping = options.mapping,
            db      = [];

        if(data[0].length < mapping.length) throw "CSV file can not be mapped";
        for(let i = Number.parseInt(options.firstLine); i < data.length; i++) {
            let line   = data[i],
                object = {};

            for(let j = 0; j < mapping.length; j++) {
                let field = mapping[j];

                if(field.length !== 0) {
                    let value = line[j];

                    if(value === undefined) continue;

                    let targetField = field;
                    if(field === 'tagIds') {
                        targetField = 'tags'
                    } else if(field === 'folderId') {
                        targetField = 'folder'
                    } else if(field === 'parentId') {
                        targetField = 'parent'
                    }

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

        if(value === boolYes || value === boolNo) {
            return value === boolYes;
        } else if(value === 'yes' || value === 'no') {
            return value === 'yes';
        } else if(value === 'false' || value === 'true') {
            return value === 'true';
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
     * @returns {Promise<{tags: Promise<Array>, folders: Promise<Array>}>}
     * @private
     */
    static async _convertCsv(data, options) {

        let [tags, tagMapping] = await ImportCsvConversionHelper._generateTags(data, options);
        let [folders, folderMapping] = await ImportCsvConversionHelper._generateFolders(data, options);

        let db = {
            tags   : tags,
            folders: folders,
        };

        return db;
    }

    /**
     *
     * @param db
     * @param options
     * @returns {Promise<Array>}
     * @private
     */
    static async _generateTags(db, options) {
        if(options.mapping.indexOf('tagLabels') === -1) return [];
        let tags    = [],
            mapping = await ImportCsvConversionHelper._getTagLabelMapping();

        for(let i = 0; i < db.length; i++) {
            let element = db[i];

            if(!element.hasOwnProperty('tagLabels')) continue;
            if(!element.hasOwnProperty('tags')) element.tags = [];

            for(let j = 0; j < element.tagLabels.length; j++) {
                let label = element.tagLabels[j];

                if(!mapping.hasOwnProperty(label)) {
                    tags.push({id: label, label: label, color: randomMC.getColor()});
                    element.tags[j] = mapping[label];
                    mapping[label] = label;
                } else {
                    element.tags[j] = mapping[label]
                }
            }
        }

        return [tags, mapping];
    }

    /**
     *
     * @param db
     * @param options
     * @returns {Promise<Array>}
     * @private
     */
    static async _generateFolders(db, options) {
        if(options.mapping.indexOf('folderLabel') === -1 && options.mapping.indexOf('parentLabel') === -1) return [];
        let folders = [],
            mapping = await ImportCsvConversionHelper._getFolderLabelMapping();

        for(let i = 0; i < db.length; i++) {
            let element = db[i];

            if((element.hasOwnProperty('folder') && mapping.hasOwnProperty(element.folder) &&
                element.hasOwnProperty('parent') && mapping.hasOwnProperty(element.parent)) ||
               (!element.hasOwnProperty('folderLabel') && !element.hasOwnProperty('parentLabel'))
            ) {
                continue;
            }

            if(element.hasOwnProperty('folderLabel')) {
                let label = element.folderLabel;
                if(!mapping.hasOwnProperty(label)) {
                    folders.push({id: label, label: label});
                    element.folder = label;
                    mapping[label] = label;
                } else {
                    element.folder = mapping[label];
                }
            }
            if(element.hasOwnProperty('parentLabel')) {
                let label = element.parentLabel;
                if(!mapping.hasOwnProperty(label)) {
                    folders.push({id: label, label: label});
                    element.parent = label;
                    mapping[label] = label;
                } else {
                    element.parent = mapping[label];
                }
            }
        }

        return [folders, mapping];
    }

    /**
     *
     * @returns {Promise<{}>}
     * @private
     */
    static async _getTagLabelMapping() {
        let tags    = await API.listTags(),
            mapping = {};

        for(let i in tags) {
            if(!tags.hasOwnProperty(i)) continue;
            let id = tags[i].id;
            mapping[tags[i].label] = id;
            mapping[id] = id;
        }

        return mapping;
    }

    /**
     *
     * @returns {Promise<{}>}
     * @private
     */
    static async _getFolderLabelMapping() {
        let folders = await API.listFolders(),
            mapping = {};

        for(let i in folders) {
            if(!folders.hasOwnProperty(i)) continue;
            let id = folders[i].id;
            mapping[folders[i].label] = id;
            mapping[id] = id;
        }

        return mapping;
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
        }
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
                mapping  : ['label', 'username', 'password', 'notes', 'url', 'folderLabel', 'edited', 'favourite', 'tagLabels', 'id', 'revision', 'folderId']
            },
            folders  : {
                firstLine: 1,
                db       : 'folders',
                delimiter: ',',
                mapping  : ['label', 'parentLabel', 'edited', 'favourite', 'id', 'revision', 'parentId']
            },
            tags     : {
                firstLine: 1,
                delimiter: ',',
                db       : 'tags',
                mapping  : ['label', 'color', 'edited', 'favourite', 'id', 'revision']
            },
            legacy   : {
                firstLine: 1,
                db       : 'passwords',
                delimiter: ',',
                mapping  : ['label', 'username', 'password', 'url', 'notes'],
                repair   : true
            }
        };

        return profiles[name];
    }
}