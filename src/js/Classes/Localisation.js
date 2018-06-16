export default class Localisation {

    /**
     *
     * @param text
     * @param variables
     * @returns {string}
     */
    static translate(text, variables = {}) {
        if(text === undefined) return '';
        if(OC !== undefined) return OC.L10N.translate('passwords', text, variables);

        return '';
    }

    static translateArray(text) {
        return Array.isArray(text) ? Localisation.translate(text[0], text[1]):Localisation.translate(text);
    }

    /**
     *
     * @param date
     * @returns {string}
     */
    static formatDate(date) {
        return OC.Util.relativeModifiedDate(date);
    }

    /**
     *
     * @param date
     * @returns {string}
     */
    static formatDateTime(date) {
        return OC.Util.formatDate(date, 'LLL');
    }
}