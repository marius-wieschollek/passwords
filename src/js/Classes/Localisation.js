import {getLanguage, getLocale, translate} from '@nextcloud/l10n';
import {generateFilePath, generateUrl}     from '@nextcloud/router';
import relativeTime                        from 'dayjs/plugin/relativeTime';
import localizedFormat                     from 'dayjs/plugin/localizedFormat';
import dayjs                               from 'dayjs';
import 'dayjs/locale/ar';
import 'dayjs/locale/az';
import 'dayjs/locale/be';
import 'dayjs/locale/bg';
import 'dayjs/locale/bi';
import 'dayjs/locale/bm';
import 'dayjs/locale/bn';
import 'dayjs/locale/bo';
import 'dayjs/locale/br';
import 'dayjs/locale/bs';
import 'dayjs/locale/ca';
import 'dayjs/locale/cs';
import 'dayjs/locale/cv';
import 'dayjs/locale/cy';
import 'dayjs/locale/da';
import 'dayjs/locale/de';
import 'dayjs/locale/dv';
import 'dayjs/locale/el';
import 'dayjs/locale/en';
import 'dayjs/locale/eo';
import 'dayjs/locale/es';
import 'dayjs/locale/et';
import 'dayjs/locale/eu';
import 'dayjs/locale/fa';
import 'dayjs/locale/fi';
import 'dayjs/locale/fo';
import 'dayjs/locale/fr';
import 'dayjs/locale/fy';
import 'dayjs/locale/ga';
import 'dayjs/locale/gd';
import 'dayjs/locale/gl';
import 'dayjs/locale/gu';
import 'dayjs/locale/he';
import 'dayjs/locale/hi';
import 'dayjs/locale/hr';
import 'dayjs/locale/ht';
import 'dayjs/locale/hu';
import 'dayjs/locale/id';
import 'dayjs/locale/is';
import 'dayjs/locale/it';
import 'dayjs/locale/ja';
import 'dayjs/locale/jv';
import 'dayjs/locale/ka';
import 'dayjs/locale/kk';
import 'dayjs/locale/km';
import 'dayjs/locale/kn';
import 'dayjs/locale/ko';
import 'dayjs/locale/ku';
import 'dayjs/locale/ky';
import 'dayjs/locale/lb';
import 'dayjs/locale/lo';
import 'dayjs/locale/lt';
import 'dayjs/locale/lv';
import 'dayjs/locale/me';
import 'dayjs/locale/mi';
import 'dayjs/locale/mk';
import 'dayjs/locale/ml';
import 'dayjs/locale/mn';
import 'dayjs/locale/mr';
import 'dayjs/locale/ms';
import 'dayjs/locale/mt';
import 'dayjs/locale/my';
import 'dayjs/locale/nb';
import 'dayjs/locale/ne';
import 'dayjs/locale/nl';
import 'dayjs/locale/nn';
import 'dayjs/locale/pl';
import 'dayjs/locale/pt';
import 'dayjs/locale/rn';
import 'dayjs/locale/ro';
import 'dayjs/locale/ru';
import 'dayjs/locale/rw';
import 'dayjs/locale/sd';
import 'dayjs/locale/se';
import 'dayjs/locale/si';
import 'dayjs/locale/sk';
import 'dayjs/locale/sl';
import 'dayjs/locale/sq';
import 'dayjs/locale/sr';
import 'dayjs/locale/ss';
import 'dayjs/locale/sv';
import 'dayjs/locale/sw';
import 'dayjs/locale/ta';
import 'dayjs/locale/te';
import 'dayjs/locale/tg';
import 'dayjs/locale/th';
import 'dayjs/locale/tk';
import 'dayjs/locale/tr';
import 'dayjs/locale/uk';
import 'dayjs/locale/ur';
import 'dayjs/locale/uz';
import 'dayjs/locale/vi';
import 'dayjs/locale/yo';
import 'dayjs/locale/zh';

dayjs.extend(relativeTime);
dayjs.extend(localizedFormat);

class Localisation {

    get baseLanguage() {
        let language = getLanguage();
        if(!language) return 'en';
        return language.indexOf('-') === -1 ? language : language.substr(0, language.indexOf('-'));
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
        return dayjs(date).locale(this.locale.substring(0, 2)).fromNow();
    }

    /**
     *
     * @param date
     * @returns {string}
     */
    formatDateTime(date) {
        return dayjs(date).locale(this.locale.substring(0, 2)).format('LLL');
    }

    /**
     *
     * @param {String} section
     * @param {Boolean} alternative
     * @returns {Promise<boolean>}
     */
    async loadSection(section, alternative = false) {
        let language = this.language.replace('-', '_'),
            url      = generateFilePath('passwords', 'l10n', `${section}/${language}.json?_=${APP_VERSION}`);
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