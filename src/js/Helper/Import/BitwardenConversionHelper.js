import RandomColorService from '@js/Services/RandomColorService';
import CustomFieldsHelper from '@js/Helper/Import/CustomFieldsHelper';
import ImportMappingHelper from '@js/Helper/Import/ImportMappingHelper';
import LocalisationService from "@js/Services/LocalisationService";

export default class BitwardenConversionHelper {

    /**
     *
     * @param json
     * @param options
     * @returns {Promise<{data: {folders: *, passwords: *, tags: *}, errors: *}>}
     */
    static async processJson(json, options) {
        let data = JSON.parse(json);

        let {folders, folderMap} = await this._processFolders(data.items);

        let tags = [], tagMap = {};
        if(data.hasOwnProperty('folders') && Array.isArray(data.folders)) {
            let result = await this._processTags(data.folders);
            tags = result.tags;
            tagMap = result.tagMap;
        }

        if(data.hasOwnProperty('collections') && Array.isArray(data.collections)) {
            let result = await this._processTags(data.collections, tags, tagMap);
            tags = result.tags;
            tagMap = result.tagMap;
        }

        let {passwords, errors} = await this._processPasswords(data.items, tagMap, folderMap);

        return {
            data: {tags, folders, passwords},
            errors
        };
    }

    /**
     *
     * @param {Object} folders
     * @param {Object[]} tags
     * @param {Object} tagMap
     * @returns {Promise<{tagMap: *, tags: *}>}
     * @private
     */
    static async _processTags(folders, tags = [], tagMap = {}) {
        let labelMap = await ImportMappingHelper.getTagLabelMapping();

        for(let folder of folders) {
            let labelId = folder.name.toLowerCase(),
                id      = folder.id;

            if(labelMap.hasOwnProperty(labelId)) {
                tagMap[folder.id] = labelMap[labelId];
            } else {
                tagMap[folder.id] = id;
                tags.push({id, label: folder.name, color: RandomColorService.color()});
            }
        }

        return {tags, tagMap};
    }

    /**
     *
     * @param data
     * @returns {Promise<{folders: *, folderMap: *}>}
     * @private
     */
    static async _processFolders(data) {
        let folders   = [],
            folderMap = {},
            labelMap  = await ImportMappingHelper.getFolderLabelMapping();

        let active = [null, false, false, false, false];
        for(let password of data) {
            if(!password.hasOwnProperty('type')) continue;

            active[password.type] = true;
        }

        let objectTypes = [null, 'Logins', 'Notes', 'Credit Cards', 'Identities'];
        for(let i = 0; i < objectTypes.length; i++) {
            if(!active[i]) continue;
            let label   = LocalisationService.translate(objectTypes[i]),
                labelId = label.toLowerCase();

            if(labelMap.hasOwnProperty(labelId)) {
                folderMap[i] = labelMap[labelId];
            } else {
                folderMap[i] = i;
                folders.push({id: i, label});
            }
        }

        return {folders, folderMap};
    }

    /**
     *
     * @param items
     * @param tagMap
     * @param folderMap
     * @returns {Promise<{passwords: *, errors: *}>}
     * @private
     */
    static async _processPasswords(items, tagMap, folderMap) {
        let passwords   = [],
            errors      = [],
            passwordMap = await ImportMappingHelper.getPasswordLabelMapping();

        for(let item of items) {
            let folder = '00000000-0000-0000-0000-000000000000',
                tags   = [];

            if(folderMap.hasOwnProperty(item.type)) {
                folder = folderMap[item.type];
            }

            if(item.hasOwnProperty('folderId') && item.folderId !== null && tagMap.hasOwnProperty(item.folderId)) {
                tags = [tagMap[item.folderId]];
            }
            if(item.hasOwnProperty('collectionIds') && item.collectionIds !== null) {
                if(typeof item.collectionIds === 'string') {
                    item.collectionIds = [item.collectionIds];
                }

                if(Array.isArray(item.collectionIds)) {
                    for(let collectionId of item.collectionIds) {
                        if(tagMap.hasOwnProperty(collectionId)) {
                            tags.push(tagMap[collectionId]);
                        }
                    }
                }
            }

            let username = null,
                password = 'password-missing-during-import';
            if(item.hasOwnProperty('login')) {
                username = item.login.username;
                if(item.login.password !== null) password = item.login.password;
            }

            let object = {
                label   : item.name,
                favorite: item.favorite,
                notes   : item.notes,
                username,
                password,
                folder,
                tags
            };

            ImportMappingHelper.checkPasswordDuplicate(passwordMap, object);
            this._processPasswordCustomFields(object, item, errors);

            passwords.push(object);
        }

        return {passwords, errors};
    }

    /**
     *
     * @param password
     * @param item
     * @param errors
     * @private
     */
    static _processPasswordCustomFields(password, item, errors) {
        password.customFields = [];

        if(item.hasOwnProperty('login')) {
            this._processLoginFields(item, password, errors);
        }
        if(item.hasOwnProperty('card')) {
            this._processCreditCartFields(item, password, errors);
        }
        if(item.hasOwnProperty('identity')) {
            this._processIdentityFields(item, password, errors);
        }
        if(item.hasOwnProperty('fields')) {
            this._processCustomFields(item, password, errors);
        }

        if(password.customFields.length === 0) delete password.customFields;
    }

    /**
     *
     * @param item
     * @param password
     * @param errors
     * @private
     */
    static _processLoginFields(item, password, errors) {
        if(item.login.hasOwnProperty('uris')) {
            let label = LocalisationService.translate('Url'),
                uris  = item.login.uris;

            for(let i = 0; i < uris.length; i++) {
                if(!uris[i].hasOwnProperty('uri')) continue;
                let url = uris[i].uri;
                if(url.indexOf('://') === -1) {
                    url = `https://${url}`;
                }

                if(i === 0) {
                    password.url = url;
                    continue;
                }

                CustomFieldsHelper.createCustomField(password, errors, url, label, 'url');
            }
        }
        if(item.login.hasOwnProperty('totp') && item.login.totp !== null) {
            CustomFieldsHelper.createCustomField(password, errors, item.login.totp, 'otp', 'secret');
        }
    }

    /**
     *
     * @param item
     * @param password
     * @param errors
     * @private
     */
    static _processCreditCartFields(item, password, errors) {
        let card = item.card;
        if(card.hasOwnProperty('cardholderName') && card.cardholderName) password.username = card.cardholderName;
        if(card.hasOwnProperty('brand') && card.brand) CustomFieldsHelper.createCustomField(password, errors, card.brand, LocalisationService.translate('Brand'), 'text');
        if(card.hasOwnProperty('number') && card.number) CustomFieldsHelper.createCustomField(password, errors, card.number, LocalisationService.translate('Number'), 'text');
        if(card.hasOwnProperty('code') && card.code) CustomFieldsHelper.createCustomField(password, errors, card.code, LocalisationService.translate('Code'), 'secret');
        if(card.hasOwnProperty('expMonth') && card.expMonth && card.hasOwnProperty('expYear') && card.expYear) {
            CustomFieldsHelper.createCustomField(password, errors, `${card.expMonth}/${card.expYear}`, LocalisationService.translate('Expires'), 'text');
        }
    }

    /**
     *
     * @param item
     * @param password
     * @param errors
     * @private
     */
    static _processIdentityFields(item, password, errors) {
        let identity = item.identity;
        password.username = identity.username;

        for(let key in identity) {
            if(!identity.hasOwnProperty(key) || key === 'username') continue;
            let type = key === 'email' ? 'email':'text';
            CustomFieldsHelper.createCustomField(password, errors, identity[key], key.capitalize(), type);
        }
    }

    /**
     *
     * @param item
     * @param password
     * @param errors
     * @private
     */
    static _processCustomFields(item, password, errors) {
        for(let field of item.fields) {
            let type  = 'text',
                value = field.value;

            if(field.type === 1) type = 'secret';
            if(field.type === 2) value = LocalisationService.translate(value === 'true' ? 'yes':'no');

            CustomFieldsHelper.createCustomField(password, errors, value, field.name, type);
        }
    }

}