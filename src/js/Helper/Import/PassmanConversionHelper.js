import * as randomMC from 'random-material-color';
import Localisation from '@js/Classes/Localisation';
import API from "@/js/Helper/api";

export default class PassmanConversionHelper {

    /**
     *
     * @param json
     * @returns {Promise<*>}
     */
    static async processJson(json) {
        let data                = JSON.parse(json),
            tags                = await this._processTags(data),
            {passwords, errors} = this._processPasswords(data);

        return {
            data: {tags, passwords},
            errors
        };
    }

    /**
     *
     * @param db
     * @returns {Promise<Array>}
     * @private
     */
    static async _processTags(db) {
        let tags    = [],
            mapping = await this._getTagLabelMapping();

        for (let i = 0; i < db.length; i++) {
            let element = db[i];

            if (!element.tags) continue;
            for (let j = 0; j < element.tags.length; j++) {
                let label = element.tags[j].text,
                    id    = label;

                if (mapping.hasOwnProperty(label)) {
                    id = mapping[label];
                } else {
                    mapping[label] = label;
                    tags.push({id: label, label, color: randomMC.getColor()});
                }
                element.tags[j] = id;
            }
        }

        return tags;
    }

    /**
     *
     * @param db
     * @returns {{passwords: Array, errors: Array}}
     * @private
     */
    static _processPasswords(db) {
        let passwords = [], errors = [];

        for (let i = 0; i < db.length; i++) {
            let element = db[i], object = {
                id          : element.guid,
                label       : element.label,
                username    : element.username,
                password    : element.password,
                url         : element.url,
                notes       : element.description,
                edited      : element.changed,
                tags        : element.tags,
                customFields: []
            };

            this._processEmail(element, object);
            this._processCustomFields(element, object, errors);
            this._processOtpValue(element, object);
            this._checkPassword(object);

            passwords.push(object);
        }

        return {passwords, errors};
    }

    /**
     *
     * @param object
     * @private
     */
    static _checkPassword(object) {
        if (typeof object.password === 'string' && object.password.length > 0) return;

        for (let i = 0; i < object.customFields.length; i++) {
            if (object.customFields[i].type === 'secret') {
                object.password = object.customFields[i].value;
                return;
            }
        }

        object.password = 'password-missing-during-import';
    }

    /**
     *
     * @param element
     * @param object
     * @private
     */
    static _processEmail(element, object) {
        if (element.email) {
            if (!object.username || object.username.length === 0) {
                object.username = element.email;
            } else {
                let label = Localisation.translate('Email'),
                    value = element.email,
                    type  = value.match(/^[\w._-]+@.+$/) ? 'email':'text';

                object.customFields.push({label, type, value});
            }
        }
    }

    /**
     *
     * @param element
     * @param object
     * @param errors
     * @private
     */
    static _processCustomFields(element, object, errors) {
        if (element.hasOwnProperty('custom_fields') && element.custom_fields.length !== 0) {
            for (let j = 0; j < element.custom_fields.length; j++) {
                let field = element.custom_fields[j];

                if (field.field_type === 'file') {
                    this._logConversionError('"{label}" has files attached which can not be imported.', element, field, errors);
                } else if (['text', 'password'].indexOf(field.field_type) !== -1) {
                    this._processCustomField(field, element, errors, object);
                } else {
                    this._logConversionError('The type of "{field}" in "{label}" is unknown and can not be imported.', element, field, errors);
                }
            }
        }
    }

    /**
     *
     * @param field
     * @param element
     * @param errors
     * @param object
     * @private
     */
    static _processCustomField(field, element, errors, object) {
        let label = field.label,
            type  = field.field_type,
            value = field.value;

        if(value.length < 1) return;
        if(label.length < 1) label = type.capitalize();

        if (label.length > 48) {
            label = label.substr(0, 48);

            this._logConversionError('The label of "{field}" in "{label}" exceeds 48 characters and was cut.', element, field, errors);
        }

        if (value.length > 320) {
            value = value.substr(0, 320);

            this._logConversionError('The value of "{field}" in "{label}" exceeds 320 characters and was cut.', element, field, errors);
        }

        if (type === 'password') {
            type = 'secret';
        } else if (value.match(/^[\w._-]+@.+$/)) {
            type = 'email';
        } else if (value.match(/^\w+:\/\/.+$/) && value.substr(0, 11) !== 'javascript:') {
            type = 'url';
        }

        object.customFields.push({label, type, value});
    }

    /**
     *
     * @param element
     * @param object
     * @private
     */
    static _processOtpValue(element, object) {
        if (element.hasOwnProperty('otp') && element.otp.hasOwnProperty('secret')) {
            object.customFields.push({label: 'otp', type: 'secret', value: element.otp.secret});
        }
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
            mapping[tags[i].label] = tags[i].id;
        }

        return mapping;
    }

    /**
     *
     * @param text
     * @param element
     * @param field
     * @param errors
     * @private
     */
    static _logConversionError(text, element, field, errors) {
        let message = Localisation.translate(text, {label: element.label, field: field.label});
        errors.push(message);
        console.error(message, element, field);
    }
}