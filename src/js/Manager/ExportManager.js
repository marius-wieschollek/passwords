import API from '@js/Helper/api';
import Utility from "@/js/Classes/Utility";

/**
 *
 */
class ExportManager {

    constructor() {
        this.defaultFolder = '00000000-0000-0000-0000-000000000000';
    }

    // noinspection JSMethodCanBeStatic
    async exportDatabase(format = 'json', model = null, options = {}) {
        if(model === null) model = ['passwords', 'folders', 'tags'];

        let data = '';
        switch(format) {
            case 'json':
                data = await ExportManager.exportJson(model, options.includeShared);
                break;
            case 'csv':
                data = await ExportManager.exportCsv(model, options.includeShared);
                break;
            case 'customCsv':
                data = await ExportManager.exportCustomCsv(options);
                break;
            default:
                throw "Invalid export format: " + format;
        }

        return data;
    }

    /**
     *
     * @param model
     * @param includeShared
     * @returns {Promise<string>}
     */
    static async exportJson(model = [], includeShared = false) {

        let json = {version: 1};
        if(model.indexOf('passwords') !== -1) {
            json.passwords = await ExportManager.getPasswordsForExport(includeShared)
        }
        if(model.indexOf('folders') !== -1) {
            json.folders = await ExportManager.getFoldersForExport()
        }
        if(model.indexOf('tags') !== -1) {
            json.tags = await ExportManager.getTagsForExport()
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
            let data   = await ExportManager.getPasswordsForExport(includeShared),
                header = ['label', 'username', 'password', 'notes', 'url', 'folderLabel', 'edited', 'favourite', 'tagLabels', 'id', 'revision', 'folderId'];
            data = await ExportManager.createCustomCsvObject(data, header.clone());
            csv.passwords = ExportManager.convertObjectToCsv(data, header);
        }
        if(model.indexOf('folders') !== -1) {
            let data   = await ExportManager.getFoldersForExport(),
                header = ['label', 'parentLabel', 'edited', 'favourite', 'id', 'revision', 'parentId'];
            data = await ExportManager.createCustomCsvObject(data, header.clone());
            csv.folders = ExportManager.convertObjectToCsv(data, header);
        }
        if(model.indexOf('tags') !== -1) {
            let data = await ExportManager.getTagsForExport(),
                header = ['label', 'color', 'edited', 'favourite', 'id', 'revision'];
            data = await ExportManager.createCustomCsvObject(data, header.clone());
            csv.tags = ExportManager.convertObjectToCsv(data, header);
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
            data = await ExportManager.getPasswordsForExport(options.includeShared);
        } else if(options.db === 'folders') {
            data = await ExportManager.getFoldersForExport();
        } else if(options.db === 'tags') {
            data = await ExportManager.getTagsForExport();
        }

        if(options.header) header = Utility.cloneObject(options.mapping);
        data = await ExportManager.createCustomCsvObject(data, options.mapping);
        return ExportManager.convertObjectToCsv(data, header, options.delimiter);
    }

    /**
     *
     * @param db
     * @param mapping
     * @returns {Promise<Array>}
     */
    static async createCustomCsvObject(db, mapping) {
        let tagDb = {}, folderDb = {}, data = [];

        if(mapping.indexOf('folderLabel') !== -1 || mapping.indexOf('parentLabel') !== -1) {
            let folders = await API.listFolders();
            folderDb[this.defaultFolder] = Utility.translate('Home');

            for(let i in folders) {
                if(!folders.hasOwnProperty(i)) continue;
                folderDb[folders[i].id] = folders[i].label;
            }
        }

        if(mapping.indexOf('tagLabels') !== -1) {
            let tags = await API.listTags();

            for(let i in tags) {
                if(!tags.hasOwnProperty(i)) continue;
                tagDb[tags[i].id] = tags[i].label;
            }
        }

        if(mapping.indexOf('folderId') !== -1) mapping[mapping.indexOf('folderId')] = 'folder';
        if(mapping.indexOf('parentId') !== -1) mapping[mapping.indexOf('parentId')] = 'parent';
        if(mapping.indexOf('tagIds') !== -1) mapping[mapping.indexOf('tagIds')] = 'tags';

        for(let i in db) {
            if(!db.hasOwnProperty(i)) continue;
            let element = db[i], object = {};

            for(let j = 0; j < mapping.length; j++) {
                let field = mapping[j];

                if(field === 'folderLabel') {
                    object.folderLabel = folderDb.hasOwnProperty(element.folder) ? folderDb[element.folder]:'';
                } else if(field === 'parentLabel') {
                    object.parentLabel = folderDb.hasOwnProperty(element.parent) ? folderDb[element.parent]:'';
                } else if(field === 'tagLabels') {
                    object.tagLabels = [];
                    for(let k in element.tags) {
                        if(!element.tags.hasOwnProperty(k)) continue;
                        object.tagLabels.push(tagDb[element.tags[k]]);
                    }
                } else if(field === 'edited' || field === 'updated' || field === 'created') {
                    object[field] = new Date(element[field] * 1e3).toString();
                } else {
                    object[field] = element[field];
                }
            }

            data.push(object);
        }

        return data;
    }

    /**
     *
     * @param object
     * @param header
     * @param delimiter
     * @returns {string}
     */
    static convertObjectToCsv(object, header = [], delimiter = ',') {
        let csv = [];

        if(header && header.length !== 0) {
            let line = [];

            for(let i = 0; i < header.length; i++) {
                line.push('"' + Utility.translate(header[i].capitalize()).replace('"', '\\"') + '"');
            }

            csv.push(line.join(delimiter));
        }

        for(let i = 0; i < object.length; i++) {
            let element = object[i],
                line    = [];

            for(let j in element) {
                if(!element.hasOwnProperty(j)) continue;
                let value = element[j];

                if(typeof value === 'boolean') value = Utility.translate(value.toString());

                line.push('"' + value.toString().replace('"', '\\"') + '"');
            }

            csv.push(line.join(delimiter));
        }

        return csv.join("\n");
    }

    /**
     *
     * @returns {Promise<Array>}
     */
    static async getPasswordsForExport(includeShared = false) {
        let data = await API.listPasswords('model+tags');

        let passwords = [];
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
     */
    static async getFoldersForExport() {
        let data = await API.listFolders();

        let folders = [];
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
     */
    static async getTagsForExport() {
        let data = await API.listTags();

        let tags = [];
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

let EM = new ExportManager();

export default EM;