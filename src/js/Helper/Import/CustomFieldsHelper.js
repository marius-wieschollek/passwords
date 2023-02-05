import Localisation from '@js/Classes/Localisation';
import Logger from "@js/Classes/Logger";

export default class CustomFieldsHelper {


    /**
     *
     * @param password
     * @param errors
     * @param value
     * @param label
     * @param type
     */
    static createCustomField(password, errors, value, label = '', type = 'text') {
        if(value === null || value === undefined) return;
        if(type === 'totp') label = 'otp';

        if(['password', 'totp'].indexOf(type) !== -1) {
            type = 'secret';
        }

        if(['text', 'secret', 'email', 'url', 'file', 'data'].indexOf(type) === -1) {
            type = 'text';
        }

        if(label.length < 1) label = type.capitalize();
        if(label.length > 48) {
            this._logConversionError('The label of "{field}" in "{label}" exceeds 48 characters and was cut.', {label: password.label, field: label}, errors);
            label = label.substr(0, 48);
        }

        if(value.length > 320) {
            this._logConversionError('The value of "{field}" in "{label}" exceeds 320 characters and was cut.', {label: password.label, field: label}, errors);
            value = value.substr(0, 320);
        }

        password.customFields.push({label, type, value});
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
        Logger.error(message, vars);
    }
}