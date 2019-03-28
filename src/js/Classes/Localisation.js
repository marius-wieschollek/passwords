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
     * @returns {Promise<any>}
     */
    static loadSection(section) {
        let language = OC.getLanguage().replace('-', '_');
        if (language === 'en') return;
        let url = OC.filePath('passwords', 'l10n', `${section}/${language}.json`);

        return new Promise((resolve, reject) => {
            fetch(new Request(url))
                .then((response) => {
                    response.json()
                        .then((d) => {
                            if (response.ok) {
                                if (d.translations) {
                                    let translations = d.translations;
                                    if(Array.isArray(translations)) {
                                        translations = Object.assign.apply(this, translations);
                                    }

                                    OC.L10N.register('passwords', translations, d.pluralForm);
                                }
                                resolve();
                            } else {
                                reject(d);
                            }
                        });
                });
        });
    }
}