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
                data = await ExportManager.exportJson(model);
                break;
            case 'csv':
                data = await ExportManager.exportCsv(model);
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
     * @returns {Promise<string>}
     */
    static async exportJson(model = []) {

        let json = {version: 1};
        if(model.indexOf('passwords') !== -1) {
            json.passwords = await ExportManager.getPasswordsForExport()
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
     * @returns {Promise<{}>}
     */
    static async exportCsv(model = []) {
        let csv = {};

        if(model.indexOf('passwords') !== -1) {
            let data = await ExportManager.getPasswordsForExport();
            csv.passwords = ExportManager.convertObjectToCsv(data, ['id', 'revision', 'label', 'username', 'password', 'notes', 'url', 'folder', 'edited', 'favourite', 'tags']);
        }
        if(model.indexOf('folders') !== -1) {
            let data = await ExportManager.getFoldersForExport();
            csv.folders = ExportManager.convertObjectToCsv(data, ['id', 'revision', 'label', 'parent', 'edited', 'favourite']);
        }
        if(model.indexOf('tags') !== -1) {
            let data = await ExportManager.getTagsForExport();
            csv.tags = ExportManager.convertObjectToCsv(data, ['id', 'revision', 'label', 'color', 'edited', 'favourite']);
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
        let header = [];

        if(options.header) {
            header = options.mapping;
        }

        if(options.db === 'passwords') {
            let data = await ExportManager.getPasswordsForExport();
            data = await ExportManager.createCustomCsvObject(data, options.mapping);
            return ExportManager.convertObjectToCsv(data, header, options.delimiter);
        }
    }

    /**
     *
     * @param db
     * @param mapping
     * @returns {Promise<Array>}
     */
    static async createCustomCsvObject(db, mapping) {
        let tagDb = {}, folderDb = {}, data = [];

        if(mapping.indexOf('folderlabel') !== -1 || mapping.indexOf('parentlabel') !== -1) {
            let folders = await API.listFolders();
            folderDb[this.defaultFolder] = Utility.translate('Home');

            for(let i in folders) {
                if(!folders.hasOwnProperty(i)) continue;
                folderDb[folders[i].id] = folders[i].label;
            }
        }

        if(mapping.indexOf('taglabels') !== -1) {
            let tags = await API.listTags();

            for(let i in tags) {
                if(!tags.hasOwnProperty(i)) continue;
                tagDb[tags[i].id] = tags[i].label;
            }
        }

        for(let i in db) {
            if(!db.hasOwnProperty(i)) continue;
            let element = db[i], object = {};

            for(let j = 0; j < mapping.length; j++) {
                let field = mapping[j];

                if(field === 'folderlabel') {
                    object.folderlabel = folderDb.hasOwnProperty(element.folder) ? folderDb[element.folder]:'';
                } else if(field === 'parentlabel') {
                    object.parentlabel = folderDb.hasOwnProperty(element.parent) ? folderDb[element.parent]:'';
                } else if(field === 'taglabels') {
                    object.taglabels = [];
                    for(let k in element.tags) {
                        if(!element.tags.hasOwnProperty(k)) continue;
                        object.taglabels.push(tagDb[element.tags[k]]);
                    }
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
    static async getPasswordsForExport() {
        let data = await API.listPasswords('model+tags');

        let passwords = [];
        for(let i in data) {
            if(!data.hasOwnProperty(i)) continue;
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