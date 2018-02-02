import API from '@js/Helper/api';
import Utility from "@/js/Classes/Utility";

/**
 *
 */
class ExportManager {

    // noinspection JSMethodCanBeStatic
    async exportDatabase(format = 'json', model = null) {
        if(model === null) model = ['passwords', 'folders', 'tags'];

        let data = '';
        switch(format) {
            case 'json':
                data = await ExportManager.exportJson(model);
                break;
            case 'csv':
                data = await ExportManager.exportCsv(model);
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
     * @param object
     * @param header
     * @param delimiter
     * @returns {string}
     */
    static convertObjectToCsv(object, header, delimiter = ',') {
        let csv = [];

        if(Array.isArray(header)) {
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