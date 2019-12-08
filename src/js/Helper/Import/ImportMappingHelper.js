import API from '@js/Helper/api';

export default class ImportMappingHelper {

    /**
     * @returns {Promise<{}>}
     */
    static async getTagLabelMapping() {
        let tags    = await API.listTags(),
            mapping = {};

        for(let i in tags) {
            if(!tags.hasOwnProperty(i)) continue;
            mapping[tags[i].label.toLowerCase()] = tags[i].id;
        }

        return mapping;
    }

    /**
     * @returns {Promise<{}>}
     */
    static async getFolderLabelMapping() {
        let folders = await API.findFolders({parent: '00000000-0000-0000-0000-000000000000'}),
            mapping = {};

        for(let i in folders) {
            if(!folders.hasOwnProperty(i)) continue;
            mapping[folders[i].label.toLowerCase()] = folders[i].id;
        }

        return mapping;
    }

    /**
     * @returns {Promise<{}>}
     */
    static async getPasswordLabelMapping() {
        let passwords = await API.listPasswords(),
            mapping   = {};

        for(let i in passwords) {
            if(!passwords.hasOwnProperty(i)) continue;
            mapping[passwords[i].label.toLowerCase()] = {
                id    : passwords[i].id,
                folder: passwords[i].folder
            };
        }

        return mapping;
    }

    /**
     * @param mapping
     * @param password
     */
    static checkPasswordDuplicate(mapping, password) {
        let id = password.label.toLowerCase();

        if(mapping.hasOwnProperty(id)) {
            let entry = mapping[id];

            if(entry.folder === password.folder) {
                password.id = entry.id;
            }
        }
    }
}