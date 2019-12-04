import API from "@js/Helper/api";
import Localisation from "@js/Classes/Localisation";

export default class BitwardenConversionHelper {

    static async processJson(json, options) {
        let data = JSON.parse(json);

        let {folders, folderMap} = await this._processFolders(data.folders);
        //let {tags, tagMap} = await this._processTags(data.items);
        let passwords = await this._processPasswords(data.items, folderMap);

        return {
            data  : {tags: [], folders, passwords},
            errors: []
        };
    }

    static async _processFolders(data) {
        let folders   = [],
            folderMap = {},
            labelMap  = await this._getFolderLabelMapping();

        for(let folder of data) {
            let labelId = folder.name.toLowerCase(),
                id      = folder.id;

            if(labelMap.hasOwnProperty(labelId)) id = labelMap[labelId];

            folderMap[folder.id] = id;
            folders.push({label: folder.name, id: id});
        }

        return {folders, folderMap};
    }

    static async _processTags(data) {
        let tags     = [],
            labelMap = {};

        for(let password of data) {
            if(tags.hasOwnProperty(password.type)) continue;


        }
    }

    static async _processPasswords(items, folderMap) {
        let passwords   = [],
            passwordMap = await this._getPasswordLabelMapping();

        for(let item of items) {
            let folder = '00000000-0000-0000-0000-000000000000';

            if(folderMap.hasOwnProperty(item.folderId)) {
                folder = folderMap[item.folderId];
            }

            let username = null,
                password = 'password-missing-during-import';

            if(item.hasOwnProperty('login')) {
                username = item.login.username;
                password = item.login.password;
            }

            let object = {
                label   : item.name,
                favorite: item.favorite,
                notes   : item.notes,
                username,
                password,
                folder
            }

            this._checkPasswordDuplicate(passwordMap, object);
            this._processPasswordCustomFields(object, item);

            passwords.push(object);
        }

        return passwords;
    }

    static _processPasswordCustomFields(password, item) {
        let customFields = [];

        if(item.hasOwnProperty('fields')) {
            for(let field of item.fields) {
                let type  = 'text',
                    value = field.value;

                if(field.type === 1) type = 'secret';
                if(field.type === 2) value = Localisation.translate(value === 'true' ? 'yes':'no')

                customFields.push({label: field.name, type, value})
            }
        }
        if(item.hasOwnProperty('card')) {
            let card = item.card;
            password.username = card.cardholderName;
            customFields.push({label: Localisation.translate('Brand'), type: 'text', value: card.brand});
            customFields.push({label: Localisation.translate('Number'), type: 'text', value: card.number});
            customFields.push({label: Localisation.translate('Code'), type: 'secret', value: card.code});
            customFields.push({label: Localisation.translate('Expires'), type: 'text', value: `${card.expMonth}/${card.expYear}`});
        }
        if(item.hasOwnProperty('identity')) {
            let identity = item.identity;
            password.username = identity.username;

            for(let key in identity) {
                if(!identity.hasOwnProperty(key) || key === 'username') continue;
                let value = identity[key],
                    label = key.capitalize(),
                    type  = key === 'email' ? 'email':'text';

                customFields.push({label, type, value});
            }
        }
        if(item.hasOwnProperty('login')) {
            if(item.login.hasOwnProperty('uris')) {
                let label = Localisation.translate('Url'),
                    uris = item.login.uris;

                for(let i = 0; i < uris.length; i++) {
                    if(!uris[i].hasOwnProperty('uri')) continue;

                    if(i === 0) {
                        password.url = uris[i].uri;
                        continue;
                    }

                    customFields.push({label, type: 'url', value: uris[i].uri});
                }
            }
            if(item.login.hasOwnProperty('totp') && item.login.totp !== null) {
                customFields.push({label: 'otp', type: 'secret', value: item.login.totp});
            }
        }

        password.customFields = customFields;
    }

    /**
     *
     * @param mapping
     * @param id
     * @param password
     * @private
     */
    static _checkPasswordDuplicate(mapping, password) {
        let id = password.label.toLowerCase();

        if(mapping.hasOwnProperty(id)) {
            let entry = mapping[id];

            if(entry.folder === password.folder) {
                password.id = entry.id;
            }
        }
    }

    /**
     *
     * @returns {Promise<{}>}
     * @private
     */
    static async _getFolderLabelMapping() {
        let folders = await API.findFolders({parent: '00000000-0000-0000-0000-000000000000'}),
            mapping = {};

        for(let i in folders) {
            if(!folders.hasOwnProperty(i)) continue;
            mapping[folders[i].label.toLowerCase()] = folders[i].id;
        }

        return mapping;
    }

    /**
     *
     * @returns {Promise<{}>}
     * @private
     */
    static async _getPasswordLabelMapping() {
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
}