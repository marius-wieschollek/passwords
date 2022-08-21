import moment                              from '@nextcloud/moment';
import {getLanguage, getLocale, translate} from '@nextcloud/l10n';
import {generateFilePath, generateUrl}     from '@nextcloud/router';

class Localisation {

    get baseLanguage() {
        let language = getLanguage();
        if(!language) return 'en';
        return language.indexOf('-') === -1  ? language:language.substr(0, language.indexOf('-'));
    }

    get language() {
        let language = getLanguage();
        if(!language) return 'en';
        return language;
    }

    get locale() {
        let locale = getLocale();
        if(locale) return locale;
        let language = getLanguage();
        if(language) return language.replace('-', '_');
        return 'en';
    }

    /**
     *
     * @param {string} text
     * @param {Object} variables
     * @returns {string}
     */
    translate(text, variables = {}) {
        if(text === undefined) return '';
        if(OC !== undefined) return translate('passwords', text, variables).replace('&amp;', '&');

        return '';
    }

    /**
     *
     * @param {string} text
     */
    translateArray(text) {
        return Array.isArray(text) ? this.translate(text[0], text[1]) : this.translate(text);
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
     * @param {String} section
     * @param {Boolean} alternative
     * @returns {Promise<boolean>}
     */
    async loadSection(section, alternative = false) {
        let language = this.language.replace('-', '_'),
            url = generateFilePath('passwords', 'l10n', `${section}/${language}.json?_=${APP_VERSION}`);
        if(alternative) {
            url = generateUrl(`/apps/passwords/l10n/${section}/${language}.json`);
        }

        let result = await this._loadFile(url);
        if(!result && !alternative) {
            return await this.loadSection(section, true);
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

            OC.L10N.register('passwords', translations);

            return true;
        }

        return false;
    }
}

export default new Localisation();