import * as randomMC from "random-material-color";
import API from '@js/Helper/api';
import Localisation from "@/js/Classes/Localisation";

export default class EnpassConversionHelper {

    /**
     *
     * @param json
     * @param options
     * @returns {Promise<{data: {tags: Array, folders: Array, passwords: Array}, errors: Array}>}
     */
    static async processJson(json, options) {
        let data                = JSON.parse(json),
            {tags, tagMap}      = await this._processTags(data.folders),
            folders             = await this._processFolders(data.items),
            {passwords, errors} = await this._processPasswords(data.items, tagMap, options);

        return {
            data: {tags, folders, passwords},
            errors
        };
    }

    /**
     *
     * @param data
     * @returns {Promise<{tags: Array, tagMap}>}
     * @private
     */
    static async _processTags(data) {
        let tags     = [],
            tagMap   = {},
            labelMap = await this._getTagLabelMapping();

        for (let i = 0; i < data.length; i++) {
            let tag = data[i],
                id  = tag.title.toLowerCase();

            if (id === '') continue;
            if (!labelMap.hasOwnProperty(id)) {
                labelMap[id] = tag.uuid;
                tagMap[tag.uuid] = tag.uuid;
                tags.push({id: tag.uuid, label: tag.title, color: randomMC.getColor()});
            } else {
                tagMap[tag.uuid] = labelMap[id];
            }
        }

        return {tags, tagMap};
    }

    /**
     *
     * @returns {Promise<{}>}
     * @private
     */
    static async _getTagLabelMapping() {
        let tags    = await API.listTags(),
            mapping = {};

        for (let i in tags) {
            if (!tags.hasOwnProperty(i)) continue;
            mapping[tags[i].label.toLowerCase()] = tags[i].id;
        }

        return mapping;
    }

    /**
     *
     * @param data
     * @returns {Promise<Array>}
     * @private
     */
    static async _processFolders(data) {
        let folders  = [],
            labelMap = await this._getFolderLabelMapping();

        for (let i = 0; i < data.length; i++) {
            let folder = data[i].category,
                id     = folder.toLowerCase();

            if (!labelMap.hasOwnProperty(id)) {
                labelMap[id] = folder;
                folders.push({id, label: folder.capitalize()});
            } else {
                data[i].category = labelMap[id];
            }
        }

        return folders;
    }


    /**
     *
     * @returns {Promise<{}>}
     * @private
     */
    static async _getFolderLabelMapping() {
        let folders = await API.findFolders({parent: '00000000-0000-0000-0000-000000000000'}),
            mapping = {
                'password': '00000000-0000-0000-0000-000000000000',
                'login'   : '00000000-0000-0000-0000-000000000000'
            };

        for (let i in folders) {
            if (!folders.hasOwnProperty(i)) continue;
            mapping[folders[i].label.toLowerCase()] = folders[i].id;
        }

        return mapping;
    }

    /**
     *
     * @param data
     * @param tagMap
     * @param options
     * @returns {Promise<{passwords: Array, errors: Array}>}
     * @private
     */
    static async _processPasswords(data, tagMap, options) {
        let passwords = [],
            errors    = [],
            mapping   = await this._getPasswordLabelMapping();

        for (let i = 0; i < data.length; i++) {
            let password = this._processPassword(data[i], mapping, tagMap, options.skipEmpty, errors);
            passwords.push(password);
        }

        return {passwords, errors};
    }

    /**
     *
     * @param element
     * @param mapping
     * @param tagMap
     * @param skipEmpty
     * @param errors
     * @returns {{customFields: Array, password: string, favorite: boolean, folder: string, label: string, notes: string}}
     * @private
     */
    static _processPassword(element, mapping, tagMap, skipEmpty, errors) {
        let id       = element.title.toLowerCase(),
            password = {
                customFields: [],
                password    : 'password-missing-during-import',
                favorite    : element.favorite === 1,
                folder      : element.category,
                label       : element.title,
                notes       : element.note,
                tags        : []
            };

        this._checkPasswordDuplicate(mapping, id, password);
        this._processPasswordTags(element, password, tagMap);

        if (element.hasOwnProperty('fields')) {
            this._processPasswordFields(element, password, skipEmpty, errors);
        }

        if (element.hasOwnProperty('attachments')) {
            this._logConversionError('"{label}" has files attached which can not be imported.', password, errors);
        }

        return password;
    }

    /**
     *
     * @param element
     * @param password
     * @param skipEmpty
     * @param errors
     * @private
     */
    static _processPasswordFields(element, password, skipEmpty, errors) {
        let commonFields = {password: false, username: false, url: false};
        for (let i = 0; i < element.fields.length; i++) {
            let field = element.fields[i];

            if (field.type === 'section') continue;
            if (skipEmpty && field.value === '') continue;
            if (field.value !== '' && this._processIfCommonField(commonFields, field, password)) continue;

            this._processCustomField(field, password, errors);
        }
    }

    /**
     *
     * @param field
     * @param errors
     * @param password
     * @private
     */
    static _processCustomField(field, password, errors) {
        let type  = 'text',
            label = field.label,
            value = field.value;
        if (['email', 'url'].indexOf(field.type) !== -1) {
            type = field.type;
        } else if (['password', 'totp'].indexOf(field.type) !== -1 || field.sensitive === 1) {
            type = 'secret';
        }

        if (label.length < 1) label = field.type.capitalize();
        if (label.length > 48) {
            this._logConversionError('The label of "{field}" in "{label}" exceeds 48 characters and was cut.', {label: password.label, field: label}, errors);
            label = label.substr(0, 48);
        }

        if (value.length > 320) {
            this._logConversionError('The value of "{field}" in "{label}" exceeds 320 characters and was cut.', {label: password.label, field: label}, errors);
            value = value.substr(0, 320);
        }

        password.customFields.push({label, type, value})
    }

    /**
     *
     * @param baseFields
     * @param field
     * @param password
     * @returns {boolean}
     * @private
     */
    static _processIfCommonField(baseFields, field, password) {
        if (!baseFields.password && field.type === 'password') {
            baseFields.password = true;
            password.password = field.value;
            password.edited = field.value_updated_at;
            return true;
        } else if (!baseFields.username && field.type === 'username') {
            baseFields.username = true;
            password.username = field.value;
            return true;
        } else if (!baseFields.url && field.type === 'url') {
            baseFields.url = true;
            password.url = field.value;
            return true;
        }
        return false;
    }

    /**
     *
     * @param element
     * @param password
     * @param tagMap
     * @private
     */
    static _processPasswordTags(element, password, tagMap) {
        if (element.hasOwnProperty('folders')) {
            for (let i = 0; i < element.folders.length; i++) {
                let id = element.folders[i].toLowerCase();

                if (tagMap.hasOwnProperty(id)) password.tags.push(tagMap[id]);
            }
        }
    }

    /**
     *
     * @param mapping
     * @param id
     * @param password
     * @private
     */
    static _checkPasswordDuplicate(mapping, id, password) {
        if (mapping.hasOwnProperty(id)) {
            let entry = mapping[id];

            if (entry.folder === password.folder) {
                password.id = entry.id;
            }
        }
    }

    /**
     *
     * @returns {Promise<{}>}
     * @private
     */
    static async _getPasswordLabelMapping() {
        let passwords = await API.listPasswords(),
            mapping   = {};

        for (let i in passwords) {
            if (!passwords.hasOwnProperty(i)) continue;
            mapping[passwords[i].label.toLowerCase()] = {
                id    : passwords[i].id,
                folder: passwords[i].folder
            };
        }

        return mapping;
    }

    /**
     *
     * @param text
     * @param vars
     * @param errors
     * @private
     */
    static _logConversionError(text, vars, errors) {
        let message = Localisation.translate(text, vars);
        errors.push(message);
        console.error(message, vars);
    }
}