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

    /**
     *
     * @param date
     * @returns {string}
     */
    static formatDate(date) {
        return date.toLocaleDateString(Localisation.getLocale(), { year: 'numeric', month: 'short', day: 'numeric' });
    }

    /**
     *
     * @param date
     * @returns {string}
     */
    static formatDateTime(date) {
        return date.toLocaleDateString(Localisation.getLocale(), { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit' });
    }

    /**
     *
     * @returns {string}
     */
    static getLocale() {
        let locale = navigator.language;

        return locale.length === 2 ? locale + '-' + locale.toUpperCase():locale;
    }
}