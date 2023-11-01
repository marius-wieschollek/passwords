/*
 * @copyright 2023 Passwords App
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the Passwords App
 * created by Marius David Wieschollek.
 */

import {getLanguage, getLocale, translate} from '@nextcloud/l10n';
import {generateFilePath, generateUrl}     from '@nextcloud/router';
import relativeTime                        from 'dayjs/plugin/relativeTime';
import localizedFormat                     from 'dayjs/plugin/localizedFormat';
import dayjs                               from 'dayjs';

import 'dayjs/locale/de';
import 'dayjs/locale/fr';
import 'dayjs/locale/it';
import 'dayjs/locale/es';
import 'dayjs/locale/nl';
import Logger from "@js/Classes/Logger";

dayjs.extend(relativeTime);
dayjs.extend(localizedFormat);

let locale = getLocale().substring(0, 2);
if(['en', 'de', 'fr', 'it', 'es', 'nl'].indexOf(locale) !== -1) {
    dayjs.locale(locale);
} else if([
    'ar', 'az',
    'be', 'bg', 'bi', 'bm', 'bn', 'bo', 'br', 'bs',
    'ca', 'cs', 'cv', 'cy',
    'da', 'dv',
    'el', 'eo', 'et', 'eu',
    'fa', 'fi', 'fo', 'fy',
    'ga', 'gd', 'gl', 'gu',
    'he', 'hi', 'hr', 'ht', 'hu',
    'id', 'is',
    'ja', 'jv',
    'ka', 'kk', 'km', 'kn', 'ko', 'ku', 'ky',
    'lb', 'lo', 'lt', 'lv',
    'me', 'mi', 'mk', 'ml', 'mn', 'mr', 'ms', 'mt', 'my',
    'nb', 'ne', 'nn',
    'pl', 'pt',
    'rn', 'ro', 'ru', 'rw',
    'sd', 'se', 'si', 'sk', 'sl', 'sq', 'sr', 'ss', 'sv', 'sw',
    'ta', 'te', 'tg', 'th', 'tk', 'tr',
    'uk', 'ur', 'uz', 'vi',
    'yo',
    'zh'
].indexOf(locale) !== -1) {
    import(/* webpackChunkName: "dayjs_locale_" */  `dayjs/locale/${locale}.js`)
        .then(() => {
            dayjs.locale(locale);
        });
}

export default new class Localisation {

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
        return dayjs(date).fromNow();
    }

    /**
     *
     * @param date
     * @returns {string}
     */
    formatDateTime(date) {
        return dayjs(date).format('LLL');
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
            Logger.error(e);
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