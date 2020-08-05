import * as randomMC from 'random-material-color';
import Localisation from '@js/Classes/Localisation';
import CustomFieldsHelper from '@js/Helper/Import/CustomFieldsHelper';
import ImportMappingHelper from '@js/Helper/Import/ImportMappingHelper';

export default class EnpassConversionHelper {

    /**
     *
     * @param json
     * @param options
     * @returns {Promise<{data: {tags: Array, folders: Array, passwords: Array}, errors: Array}>}
     */
    static async processJson(json, options) {
        let data = JSON.parse(json);

        if(!data.items) throw new Error('File does not implement Enpass 6 format');
        if(!Array.isArray(data.folders)) data.folders = [];

        let {tags, tagMap}      = await this._processTags(data.folders),
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
            labelMap = await ImportMappingHelper.getTagLabelMapping();

        for(let i = 0; i < data.length; i++) {
            let tag = data[i],
                id  = tag.title.toLowerCase();

            if(id === '') continue;
            if(!labelMap.hasOwnProperty(id)) {
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
     * @param data
     * @returns {Promise<Array>}
     * @private
     */
    static async _processFolders(data) {
        let folders    = [],
            categories = this._getCategoryLabels(),
            labelMap   = await ImportMappingHelper.getFolderLabelMapping();

        for(let i = 0; i < data.length; i++) {
            let folder = data[i].category,
                label  = folder.capitalize();

            if(categories.hasOwnProperty(folder)) {
                label = categories[folder];
            }

            let id = label.toLowerCase();
            if(!labelMap.hasOwnProperty(id)) {
                labelMap[id] = id;
                folders.push({id, label});
            }

            data[i].category = labelMap[id];
        }

        return folders;
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
            mapping   = await ImportMappingHelper.getPasswordLabelMapping();

        for(let i = 0; i < data.length; i++) {
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
        let label = element.title;
        if(element.hasOwnProperty('subtitle') && element.subtitle.length !== 0 &&
           element.subtitle !== label &&
           (!element.hasOwnProperty('template_type') || element.template_type !== 'login.default')) {
            label = `${label} – ${element.subtitle}`;
        }

        let password = {
            customFields: [],
            password    : 'password-missing-during-import',
            favorite    : element.favorite === 1,
            folder      : element.category,
            notes       : element.note,
            label,
            tags        : []
        };

        ImportMappingHelper.checkPasswordDuplicate(mapping, password);
        this._processPasswordTags(element, password, tagMap);

        if(element.hasOwnProperty('fields')) {
            this._processPasswordFields(element, password, skipEmpty, errors);
        }

        if(element.hasOwnProperty('attachments')) {
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
        for(let i = 0; i < element.fields.length; i++) {
            let field = element.fields[i];

            if(field.type === 'section') continue;
            if(skipEmpty && field.value === '') continue;
            if(field.value !== '' && this._processIfCommonField(commonFields, field, password)) continue;

            this._processCustomField(field, password, errors);
        }
        if(password.customFields.length === 0) delete password.customFields;
    }

    /**
     *
     * @param field
     * @param errors
     * @param password
     * @private
     */
    static _processCustomField(field, password, errors) {
        let type = field.sensitive ? 'secret':field.type;
        CustomFieldsHelper.createCustomField(password, errors, field.value, field.label, type);
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
        if(!baseFields.password && field.type === 'password') {
            baseFields.password = true;
            password.password = field.value;
            password.edited = field.value_updated_at;
            return true;
        } else if(!baseFields.username && field.type === 'username') {
            baseFields.username = true;
            password.username = field.value;
            return true;
        } else if(!baseFields.url && field.type === 'url') {
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
        if(element.hasOwnProperty('folders')) {
            for(let i = 0; i < element.folders.length; i++) {
                let id = element.folders[i].toLowerCase();

                if(tagMap.hasOwnProperty(id)) password.tags.push(tagMap[id]);
            }
        }
    }

    /**
     *
     * @returns {{note: string, license: string, password: string, computer: string, identity: string, login: string, travel: string, creditcard: string, finance: string, misc: string}}
     * @private
     */
    static _getCategoryLabels() {
        return {
            login     : Localisation.translate('Logins'),
            creditcard: Localisation.translate('Credit Cards'),
            identity  : Localisation.translate('Identities'),
            note      : Localisation.translate('Notes'),
            password  : Localisation.translate('Passwords'),
            finance   : Localisation.translate('Finances'),
            license   : Localisation.translate('Licenses'),
            travel    : Localisation.translate('Travel'),
            computer  : Localisation.translate('Computers'),
            misc      : Localisation.translate('Miscellaneous')
        };
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