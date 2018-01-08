import API from '@js/Helper/api';
import Utility from "@js/Classes/Utility";

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
            default:
                throw "Invalid export format: " + format;
        }

        let date     = new Date(),
            filename = Utility.translate('Passwords') + '_' + date.toLocaleDateString() + '_' + date.toLocaleTimeString() + '.' + format;

        Utility.createDownload(data, filename);
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
                    favourite: element.favourite
                }
            );
        }

        return tags;
    }
}

let EM = new ExportManager();

export default EM;