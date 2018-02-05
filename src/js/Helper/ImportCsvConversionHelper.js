import API from "@/js/Helper/api";
import Utility from "@/js/Classes/Utility";
import SimpleApi from "@/js/ApiClient/SimpleApi";
import * as randomMC from "random-material-color";

/**
 *
 */
class ImportCsvConversionHelper {

    constructor() {
        this.defaultFolder = '00000000-0000-0000-0000-000000000000';
    }

    /**
     * Parse a generic csv file
     *
     * @param csv
     * @param options
     * @returns {{}}
     */
    async processGenericCsv(csv, options) {
        let profile = options.profile === 'custom' ? options:ImportCsvConversionHelper._getGenericProfiles(options.profile),
            data    = Utility.parseCsv(csv, profile.delimiter);
        return await this._processCsv(data, profile);
    }

    /**
     * Parse a csv file from Passman
     *
     * @param csv
     * @returns {Promise<*>}
     */
    async processPassmanCsv(csv) {
        let data = Utility.parseCsv(csv, ',');

        for(let i = 0; i < data.length; i++) {
            let line = data[i];

            data[i][5] = line[5].substr(1, line[5].length - 2)
        }

        return await this._processCsv(data, ImportCsvConversionHelper._getPassmanProfile());
    }

    /**
     *
     * @param data
     * @param options
     * @returns {Promise<{}>}
     * @private
     */
    async _processCsv(data, options) {
        let mapping = options.mapping,
            db      = [];

        if(data[0].length < mapping.length) throw "CSV file can not be mapped";
        if(options.firstLine) data.splice(0, options.firstLine);
        data = await this._processSpecialFields(data, mapping, options.db);

        for(let i = 0; i < data.length; i++) {
            let line   = data[i],
                object = {};

            for(let j = 0; j < mapping.length; j++) {
                let field = mapping[j];

                if(field.length !== 0) {
                    let value = line[j];

                    if(value === undefined) continue;
                    object[field] = ImportCsvConversionHelper._processCsvValue(value, field);
                }
            }

            if(options.repair) ImportCsvConversionHelper._repairObject(object);
            db.push(object);
        }

        let tmp = {};
        tmp[options.db] = db;

        return tmp;
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
        } else if(field === 'tags' && value.length !== 0 && !Array.isArray(value)) {
            return value.split(',');
        }
        return value;
    }

    /**
     *
     * @param db
     * @param mapping
     * @param dbType
     * @returns {Promise<Array>}
     */
    async _processSpecialFields(db, mapping, dbType) {
        let tagDb = {}, folderDb = {}, folderIdMap = {}, idMap = {}, data = [];

        if(dbType === 'passwords' && mapping.indexOf('id') === -1) {
            await ImportCsvConversionHelper._getPasswordMapForSpecialFields(idMap);
        }

        if(mapping.indexOf('tagLabels') !== -1) {
            idMap = await ImportCsvConversionHelper._getTagMapForSpecialFields(tagDb, dbType, idMap);
        }

        if(mapping.indexOf('folderLabel') !== -1 || mapping.indexOf('parentLabel') !== -1) {
            idMap = await this._getFolderMapForSpecialFields(folderDb, folderIdMap, dbType, idMap);
        }

        if(mapping.indexOf('folderId') !== -1) mapping[mapping.indexOf('folderId')] = 'folder';
        if(mapping.indexOf('parentId') !== -1) mapping[mapping.indexOf('parentId')] = 'parent';
        if(mapping.indexOf('tagIds') !== -1) mapping[mapping.indexOf('tagIds')] = 'tags';
        if(mapping.indexOf('id') === -1) mapping.push('id');

        for(let i = 0; i < db.length; i++) {
            let element = db[i], object = element;

            for(let j = 0; j < mapping.length; j++) {
                let field = mapping[j],
                    value = element[j];

                if(field.length === 0) continue;
                object[j] = await ImportCsvConversionHelper._processSpecialField(field, value, mapping, element, idMap, dbType, folderDb, folderIdMap, tagDb);
            }

            data.push(object);
        }

        if(mapping.indexOf('folderLabel') !== -1) mapping[mapping.indexOf('folderLabel')] = 'folder';
        if(mapping.indexOf('parentLabel') !== -1) mapping[mapping.indexOf('parentLabel')] = 'parent';
        if(mapping.indexOf('tagLabels') !== -1) mapping[mapping.indexOf('tagLabels')] = 'tags';

        return data;
    }

    /**
     *
     * @param field
     * @param value
     * @param mapping
     * @param element
     * @param idMap
     * @param dbType
     * @param folderDb
     * @param folderIdMap
     * @param tagDb
     * @returns {Promise<*>}
     * @private
     */
    static async _processSpecialField(field, value, mapping, element, idMap, dbType, folderDb, folderIdMap, tagDb) {
        if(field === 'id' && value === undefined && mapping.indexOf('label') !== -1) {
            let label = element[mapping.indexOf('label')];
            if(idMap.hasOwnProperty(label)) return idMap[label];
        } else if(field === 'label' && dbType === 'folders') {
            // @TODO maybe only if id field was not in original csv
            if(!folderDb.hasOwnProperty(value)) {
                let folder = await API.createFolder({label: value});
                folderDb[value] = folder.id;
            }
        } else if(field === 'folderLabel') {
            return await this._processFolderLabelField(value, folderDb, mapping, element, folderIdMap, 'folder');
        } else if(field === 'parentLabel') {
            return await this._processFolderLabelField(value, folderDb, mapping, element, folderIdMap, 'parent');
        } else if(field === 'tagLabels' && value) {
            return await this._processTagLabelsField(value, tagDb);
        }
        return value;
    }

    /**
     *
     * @param value
     * @param tagDb
     * @returns {Promise<Array>}
     * @private
     * @TODO do something if tag id and tag labels field is present
     */
    static async _processTagLabelsField(value, tagDb) {
        let tagLabels = value.split(',');
        value = [];
        for(let k = 0; k < tagLabels.length; k++) {
            let tagLabel = tagLabels[k];

            if(!tagDb.hasOwnProperty(tagLabel)) {
                let tag = await API.createTag({label: tagLabel, color: randomMC.getColor()});
                tagDb[tagLabel] = tag.id;
            }

            value.push(tagDb[tagLabel]);
        }
        return value;
    }

    /**
     *
     * @param value
     * @param folderDb
     * @param mapping
     * @param element
     * @param folderIdMap
     * @param backupField
     * @returns {Promise<*>}
     * @private
     */
    static async _processFolderLabelField(value, folderDb, mapping, element, folderIdMap, backupField) {
        if(value === undefined) { value = Utility.translate('Home'); }
        if(!folderDb.hasOwnProperty(value)) {
            let folder = await API.createFolder({label: value});
            folderDb[value] = folder.id;
        }

        let backupFieldIndex = mapping.indexOf(backupField);
        if(backupFieldIndex !== -1) {
            let folder = element[backupFieldIndex];

            if(folderIdMap[folder] === value) {
                return folderIdMap[folder];
            } else {
                element[backupFieldIndex] = folderDb[value];
            }
        }
        return folderDb[value];
    }

    /**
     *
     * @param idMap
     * @returns {Promise<void>}
     * @private
     */
    static async _getPasswordMapForSpecialFields(idMap) {
        let passwords = await API.listPasswords();

        for(let i in passwords) {
            if(!passwords.hasOwnProperty(i)) continue;
            idMap[passwords[i].label] = passwords[i].id;
        }
    }

    /**
     *
     * @param folderDb
     * @param folderIdMap
     * @param dbType
     * @param idMap
     * @returns {Promise<*>}
     * @private
     */
    async _getFolderMapForSpecialFields(folderDb, folderIdMap, dbType, idMap) {
        let folders = await API.listFolders();
        folderDb[Utility.translate('Home')] = this.defaultFolder;

        for(let i in folders) {
            if(!folders.hasOwnProperty(i)) continue;
            folderDb[folders[i].label] = folders[i].id;
            folderIdMap[folders[i].id] = folders[i].label;
        }
        if(dbType === 'folders') idMap = folderDb;
        return idMap;
    }

    /**
     *
     * @param tagDb
     * @param dbType
     * @param idMap
     * @returns {Promise<*>}
     * @private
     */
    static async _getTagMapForSpecialFields(tagDb, dbType, idMap) {
        let tags = await API.listTags();

        for(let i in tags) {
            if(!tags.hasOwnProperty(i)) continue;
            tagDb[tags[i].label] = tags[i].id;
        }
        if(dbType === 'tags') idMap = tagDb;
        return idMap;
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

let ICCH = new ImportCsvConversionHelper();

export default ICCH;