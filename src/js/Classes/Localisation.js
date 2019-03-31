export default class Localisation {

    /**
     *
     * @param text
     * @param variables
     * @returns {string}
     */
    static translate(text, variables = {}) {
        if (text === undefined) return '';
        if (OC !== undefined) return OC.L10N.translate('passwords', text, variables);

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

    /**
     *
     * @param section
     * @returns {Promise<boolean>}
     */
    static async loadSection(section) {
        let language = OC.getLanguage().replace('-', '_');
        if (language === 'en') return true;
        let url = OC.filePath('passwords', 'l10n', `${section}/${language}.json`);

        try {
            let response = fetch(new Request(url));

            if (response.ok) {
                let data = await response.json();

                if (data.hasOwnProperty('translations')) {
                    let translations = data.translations;
                    if(Array.isArray(translations)) {
                        translations = Object.assign.apply(this, translations);
                    }

                    OC.L10N.register('passwords', translations, data.pluralForm);
                    return true;
                }
            }
        }catch (e) {
            console.error(e);
        }
        return false;
    }
}