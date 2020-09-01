import moment from '@nextcloud/moment'

class Localisation {

    constructor() {
        this._fetchAlternative = false;
    }

    /**
     *
     * @param {string} text
     * @param {Object} variables
     * @returns {string}
     */
    translate(text, variables = {}) {
        if(text === undefined) return '';
        if(OC !== undefined) return OC.L10N.translate('passwords', text, variables).replace('&amp;', '&');

        return '';
    }

    /**
     *
     * @param {string} text
     */
    translateArray(text) {
        return Array.isArray(text) ? this.translate(text[0], text[1]):this.translate(text);
    }

    /**
     *
     * @param date
     * @returns {string}
     */
    formatDate(date) {
        return moment(date).fromNow();
    }

    /**
     *
     * @param date
     * @returns {string}
     */
    formatDateTime(date) {
        return moment(date).format('LLL');
    }

    /**
     *
     * @param {string} section
     * @returns {Promise<boolean>}
     */
    async loadSection(section) {
        let language = OC.getLanguage().replace('-', '_');
        if(language === 'en') return true;

        let url = OC.filePath('passwords', 'l10n', `${section}/${language}.json`);
        if(this._fetchAlternative) {
            url = OC.generateUrl(`/apps/passwords/l10n/${section}/${language}.json?_=${process.env.APP_VERSION}`);
        }

        let result = await this._loadFile(url);
        if(!result && language === 'de') {
            this._fetchAlternative = true;
            return await this.loadSection(section);
        }

        return result;
    }

    /**
     *
     * @param {string} url
     * @return {Promise<boolean>}
     * @private
     */
    async _loadFile(url) {
        try {
            let request  = new Request(url, {redirect: 'error'}),
                response = await fetch(request);

            if(response.ok) {
                let data = await response.json();
                return this._processTranslations(data);
            }
        } catch(e) {
            console.error(e);
        }

        return false;
    }

    /**
     *
     * @param {Object} data
     * @return {boolean}
     * @private
     */
    _processTranslations(data) {
        if(data.hasOwnProperty('translations')) {
            let translations = data.translations;
            if(Array.isArray(translations)) {
                translations = Object.assign.apply(this, translations);
            }

            OC.L10N.register('passwords', translations, data.pluralForm);

            return true;
        }

        return false;
    }
}

export default new Localisation();