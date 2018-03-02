import API from '@js/Helper/api';
import Utility from "@/js/Classes/Utility";

/**
 *
 */
class SettingsManager {

    constructor() {
        this._settings = {
            'local.ui.sort.ascending'         : true,
            'local.ui.sort.field'             : 'label',
            'client.ui.password.field.title'  : 'label',
            'client.ui.password.field.sorting': 'byTitle',
            'client.ui.password.menu.copy'    : false
        };
    }

    /**
     *
     * @param setting
     * @param value
     */
    set(setting, value) {
        this._settings[setting] = value;
        SettingsManager._setSetting(setting, value);
    }

    /**
     *
     * @param setting
     * @param standard
     * @returns {*}
     */
    get(setting, standard) {
        if(this._settings.hasOwnProperty(setting)) return this._settings[setting];
        if(standard !== undefined) return standard;
        return null;
    }

    /**
     *
     */
    getAll() {
        return Utility.cloneObject(this._settings);
    }

    async init() {
        this._addSettings(
            await API.getSettings(
                [
                    'user.password.generator.strength',
                    'user.password.generator.numbers',
                    'user.password.generator.special',
                    'client.ui.password.field.title',
                    'client.ui.password.field.sorting',
                    'client.ui.password.menu.copy'
                ]
            )
        );

        if(window.localStorage.hasOwnProperty('passwords.settings')) {
            this._addSettings(
                JSON.parse(window.localStorage.getItem('passwords.settings'))
            );
        }
    }

    /**
     *
     * @param setting
     * @param value
     * @returns {Promise<*>}
     * @private
     */
    static async _setSetting(setting, value) {
        if(setting.substr(0, 6) === 'local.') {
            return SettingsManager._setLocalSetting(setting, value);
        } else {
            return await API.setSetting(setting, value);
        }
    }

    /**
     *
     * @param setting
     * @param value
     * @private
     */
    static _setLocalSetting(setting, value) {
        let settings = {};
        if(window.localStorage.hasOwnProperty('passwords.settings')) {
            settings = JSON.parse(window.localStorage.getItem('passwords.settings'));
        }

        settings[setting] = value;
        window.localStorage.setItem('passwords.settings', JSON.stringify(settings));
    }

    /**
     *
     * @param settings
     * @private
     */
    _addSettings(settings) {
        for(let i in settings) {
            if(settings.hasOwnProperty(i) && settings[i] !== null) this._settings[i] = settings[i];
        }
    }
}

let SM = new SettingsManager();

export default SM;