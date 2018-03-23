import API from '@js/Helper/api';
import Utility from '@js/Classes/Utility';
import Encryption from '@js/ApiClient/Encryption';
import Localisation from '@js/Classes/Localisation';

/**
 *
 */
export class ExportManager {

    constructor() {
        this.defaultFolder = '00000000-0000-0000-0000-000000000000';
    }

    // noinspection JSMethodCanBeStatic
    async exportDatabase(format = 'json', model = null, options = {}) {
        if(model === null) model = ['passwords', 'folders', 'tags'];

        let data = '';
        switch(format) {
            case 'json':
                data = await ExportManager.exportJson(model, options);
                break;
            case 'csv':
                data = await ExportManager.exportCsv(model, options.includeShared);
                break;
            case 'customCsv':
                data = await ExportManager.exportCustomCsv(options);
                break;
            case 'xlsx':
                data = await ExportManager.exportOfficeDocument(model, options.includeShared, 'xlsx');
                break;
            case 'ods':
                data = await ExportManager.exportOfficeDocument(model, options.includeShared, 'ods');
                break;
            default:
                throw new Error(`Invalid export format: ${format}`);
        }

        return data;
    }

    /**
     *
     * @param model
     * @param options
     * @returns {Promise<string>}
     */
    static async exportJson(model, options) {

        let json = {version: 1, encrypted: false};
        if(model.indexOf('passwords') !== -1) {
            json.passwords = await ExportManager._getPasswordsForExport(options.includeShared);
        }
        if(model.indexOf('folders') !== -1) {
            json.folders = await ExportManager._getFoldersForExport();
        }
        if(model.indexOf('tags') !== -1) {
            json.tags = await ExportManager._getTagsForExport();
        }

        if(options.password) {
            let encryption = new Encryption();

            for(let i in json) {
                if(!json.hasOwnProperty(i) || ['version', 'encrypted'].indexOf(i) !== -1) continue;
                let data = JSON.stringify(json[i]),
                    key  = options.password + i;

                json[i] = await encryption.encrypt(data, key);
            }
            json.encrypted = true;
            json.challenge = await encryption.encrypt(options.password, `${options.password}challenge`);
        }

        return JSON.stringify(json);
    }

    /**
     *
     * @param model
     * @param includeShared
     * @returns {Promise<{}>}
     */
    static async exportCsv(model = [], includeShared = false) {
        let csv = {};

        if(model.indexOf('passwords') !== -1) {
            let data   = await ExportManager._getPasswordsForExport(includeShared),
                header = ['label', 'username', 'password', 'notes', 'url', 'folderLabel', 'tagLabels', 'favourite', 'edited', 'id', 'revision', 'folderId'];
            data = await ExportManager._convertDbToExportStructure(data, header.clone());
            csv.passwords = ExportManager._convertObjectToCsv(data, header);
        }
        if(model.indexOf('folders') !== -1) {
            let data   = await ExportManager._getFoldersForExport(),
                header = ['label', 'parentLabel', 'favourite', 'edited', 'id', 'revision', 'parentId'];
            data = await ExportManager._convertDbToExportStructure(data, header.clone());
            csv.folders = ExportManager._convertObjectToCsv(data, header);
        }
        if(model.indexOf('tags') !== -1) {
            let data   = await ExportManager._getTagsForExport(),
                header = ['label', 'color', 'favourite', 'edited', 'id', 'revision'];
            data = await ExportManager._convertDbToExportStructure(data, header.clone());
            csv.tags = ExportManager._convertObjectToCsv(data, header);
        }

        if(model.length === 1) csv = csv[model[0]];

        return csv;
    }

    /**
     *
     * @param options
     * @returns {Promise<string>}
     */
    static async exportCustomCsv(options) {
        let header = [], data;

        if(options.db === 'passwords') {
            data = await ExportManager._getPasswordsForExport(options.includeShared);
        } else if(options.db === 'folders') {
            data = await ExportManager._getFoldersForExport();
        } else if(options.db === 'tags') {
            data = await ExportManager._getTagsForExport();
        }

        if(options.header) header = Utility.cloneObject(options.mapping);
        data = await ExportManager._convertDbToExportStructure(data, options.mapping);
        return ExportManager._convertObjectToCsv(data, header, options.delimiter);
    }

    /**
     *
     * @param model
     * @param includeShared
     * @param format
     * @returns {Promise<void>}
     */
    static async exportOfficeDocument(model = [], includeShared = false, format) {
        let sheets = {};
        if(model.indexOf('passwords') !== -1) {
            let data   = await ExportManager._getPasswordsForExport(includeShared),
                header = ['label', 'username', 'password', 'notes', 'url', 'folderLabel', 'tagLabels', 'favourite', 'edited', 'id', 'revision', 'folderId'];
            data = await ExportManager._convertDbToExportStructure(data, header.clone());
            sheets.passwords = ExportManager._convertObjectToOffice(data, header);
        }
        if(model.indexOf('folders') !== -1) {
            let data   = await ExportManager._getFoldersForExport(),
                header = ['label', 'parentLabel', 'favourite', 'edited', 'id', 'revision', 'parentId'];
            data = await ExportManager._convertDbToExportStructure(data, header.clone());
            sheets.folders = ExportManager._convertObjectToOffice(data, header);
        }
        if(model.indexOf('tags') !== -1) {
            let data   = await ExportManager._getTagsForExport(),
                header = ['label', 'color', 'favourite', 'edited', 'id', 'revision'];
            data = await ExportManager._convertDbToExportStructure(data, header.clone());
            sheets.tags = ExportManager._convertObjectToOffice(data, header);
        }

        try {
            let XLSX     = await import(/* webpackChunkName: "xlsx" */ 'xlsx'),
                workbook = {SheetNames: [], Sheets: {}};

            for(let i in sheets) {
                if(!sheets.hasOwnProperty(i)) continue;
                let name = Localisation.translate(i.capitalize());

                workbook.SheetNames.push(name);
                workbook.Sheets[name] = XLSX.utils.aoa_to_sheet(sheets[i]);
            }

            return XLSX.write(workbook, {bookType: format, type: 'array'});
        } catch(e) {
            throw new Error(Localisation.translate('Unable to load {module}', {module: 'xlsx'}));
        }
    }

    /**
     *
     * @param db
     * @param mapping
     * @returns {Promise<Array>}
     * @private
     */
    static async _convertDbToExportStructure(db, mapping) {
        let folderDb = await this._createExportFolderMapping(mapping),
            tagDb    = await this._createExportTagMapping(mapping),
            data     = [];

        if(mapping.indexOf('folderId') !== -1) mapping[mapping.indexOf('folderId')] = 'folder';
        if(mapping.indexOf('parentId') !== -1) mapping[mapping.indexOf('parentId')] = 'parent';
        if(mapping.indexOf('tagIds') !== -1) mapping[mapping.indexOf('tagIds')] = 'tags';

        for(let i in db) {
            if(!db.hasOwnProperty(i)) continue;
            let object = this._convertObjectForExport(db[i], mapping, folderDb, tagDb);
            data.push(object);
        }

        return data;
    }

    /**
     *
     * @param mapping
     * @returns {Promise<{}>}
     * @private
     */
    static async _createExportFolderMapping(mapping) {
        let folderDb = {};
        if(mapping.indexOf('folderLabel') !== -1 || mapping.indexOf('parentLabel') !== -1) {
            let folders = await API.listFolders();
            folderDb[this.defaultFolder] = Localisation.translate('Home');

            for(let i in folders) {
                if(!folders.hasOwnProperty(i)) continue;
                folderDb[folders[i].id] = folders[i].label;
            }
        }
        return folderDb;
    }

    /**
     *
     * @param mapping
     * @returns {Promise<{}>}
     * @private
     */
    static async _createExportTagMapping(mapping) {
        let tagDb = {};
        if(mapping.indexOf('tagLabels') !== -1) {
            let tags = await API.listTags();

            for(let i in tags) {
                if(!tags.hasOwnProperty(i)) continue;
                tagDb[tags[i].id] = tags[i].label;
            }
        }
        return tagDb;
    }

    /**
     *
     * @param element
     * @param mapping
     * @param folderDb
     * @param tagDb
     * @private
     */
    static _convertObjectForExport(element, mapping, folderDb, tagDb) {
        let object = {};
        for(let j = 0; j < mapping.length; j++) {
            let field = mapping[j];

            if(field === 'folderLabel') {
                object.folderLabel = folderDb.hasOwnProperty(element.folder) ? folderDb[element.folder]:'';
            } else if(field === 'parentLabel') {
                object.parentLabel = folderDb.hasOwnProperty(element.parent) ? folderDb[element.parent]:'';
            } else if(field === 'tagLabels') {
                object.tagLabels = ExportManager._convertTagLabelsForExport(element, tagDb);
            } else if(['edited', 'updated', 'created'].indexOf(field) !== -1) {
                object[field] = new Date(element[field] * 1e3).toString();
            } else {
                object[field] = element[field];
            }
        }
        return object;
    }

    /**
     *
     * @param element
     * @param tagDb
     * @returns {Array}
     * @private
     */
    static _convertTagLabelsForExport(element, tagDb) {
        let tagLabels = [];
        for(let k in element.tags) {
            if(!element.tags.hasOwnProperty(k)) continue;
            tagLabels.push(tagDb[element.tags[k]]);
        }
        return tagLabels;
    }

    /**
     *
     * @param object
     * @param header
     * @param delimiter
     * @returns {string}
     * @private
     */
    static _convertObjectToCsv(object, header = [], delimiter = ',') {
        let csv = [];

        if(header && header.length !== 0) {
            let line = [];

            for(let i = 0; i < header.length; i++) {
                line.push(`"${Localisation.translate(header[i].capitalize()).replace('"', '""')}"`);
            }

            csv.push(line.join(delimiter));
        }

        for(let i = 0; i < object.length; i++) {
            let element = object[i],
                line    = [];

            for(let j in element) {
                if(!element.hasOwnProperty(j)) continue;
                let value = element[j];

                if(typeof value === 'boolean') value = Localisation.translate(value.toString());

                line.push(`"${value.toString().replace(/"/g, '""')}"`);
            }

            csv.push(line.join(delimiter));
        }

        return csv.join('\n');
    }

    /**
     *
     * @param object
     * @param header
     * @returns {Promise<Array>}
     * @private
     */
    static _convertObjectToOffice(object, header = []) {
        let data = [];
        if(header && header.length !== 0) {
            let line = [];

            for(let i = 0; i < header.length; i++) {
                line.push(Localisation.translate(header[i].capitalize()));
            }

            data.push(line);
        }

        for(let i = 0; i < object.length; i++) {
            let element = object[i],
                line    = [];

            for(let j in element) {
                if(!element.hasOwnProperty(j)) continue;
                let value = element[j];

                if(typeof value === 'boolean') {
                    value = value ? 1:0;
                } else {
                    value = value.toString();
                }

                line.push(value);
            }

            data.push(line);
        }

        return data;
    }

    /**
     *
     * @returns {Promise<Array>}
     * @private
     */
    static async _getPasswordsForExport(includeShared = false) {
        let data      = await API.listPasswords('model+tags'),
            passwords = [];

        for(let i in data) {
            if(!data.hasOwnProperty(i)) continue;
            if(includeShared && data[i].share !== null) continue;
            let element  = data[i],
                password = {
                    id       : element.id,
                    revision : element.revision,
                    label    : element.label,
                    username : element.username,
                    password : element.password,
                    notes    : element.notes,
                    url      : element.url,
                    folder   : element.folder,
                    edited   : Math.floor(element.edited.getTime() / 1000),
                    favourite: element.favourite
                };

            password.tags = [];
            for(let j in element.tags) {
                if(!element.tags.hasOwnProperty(j)) continue;
                password.tags.push(element.tags[j].id);
            }

            passwords.push(password);
        }

        return passwords;
    }

    /**
     *
     * @returns {Promise<Array>}
     * @private
     */
    static async _getFoldersForExport() {
        let data    = await API.listFolders(),
            folders = [];

        for(let i in data) {
            if(!data.hasOwnProperty(i)) continue;
            let element = data[i];
            folders.push(
                {
                    id       : element.id,
                    revision : element.revision,
                    label    : element.label,
                    parent   : element.parent,
                    edited   : Math.floor(element.edited.getTime() / 1000),
                    favourite: element.favourite
                }
            );
        }

        return folders;
    }

    /**
     *
     * @returns {Promise<Array>}
     * @private
     */
    static async _getTagsForExport() {
        let data = await API.listTags(),
            tags = [];

        for(let i in data) {
            if(!data.hasOwnProperty(i)) continue;
            let element = data[i];
            tags.push(
                {
                    id       : element.id,
                    revision : element.revision,
                    label    : element.label,
                    color    : element.color,
                    edited   : Math.floor(element.edited.getTime() / 1000),
                    favourite: element.favourite
                }
            );
        }

        return tags;
    }
}