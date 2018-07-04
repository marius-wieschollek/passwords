import API from '@js/Helper/api';
import Utility from '@js/Classes/Utility';

/**
 *
 */
class SettingsManager {

    constructor() {
        this._defaults = {
            'local.ui.sort.ascending'            : true,
            'local.ui.sort.field'                : 'label',
            'client.ui.section.default'          : 'folders',
            'client.ui.password.field.title'     : 'label',
            'client.ui.password.field.sorting'   : 'byTitle',
            'client.ui.password.click.action'    : 'password',
            'client.ui.password.dblClick.action' : 'username',
            'client.ui.password.menu.copy'       : false,
            'client.ui.password.user.show'       : false,
            'client.ui.custom.fields.show.hidden': false,
            'client.ui.list.tags.show'           : false,
            'client.search.show'                 : false,
            'client.search.live'                 : true,
            'client.search.global'               : true,
            'client.settings.advanced'           : false
        };
        this._settings = Utility.cloneObject(this._defaults);
        this._observers = {};
    }

    /**
     *
     * @param setting
     * @param value
     */
    set(setting, value) {
        this._settings[setting] = value;
        SettingsManager._setSetting(setting, value);
        this._triggerObservers(setting, value);
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
     * @param setting
     * @returns {Promise<*>}
     */
    async reset(setting) {
        let [scope] = setting.split('.', 2);

        if(scope === 'local') {
            this._settings[setting] = this._resetLocalSetting(setting);
        } else if(scope === 'user' || scope === 'client') {
            this._settings[setting] = await API.resetSetting(setting);
            if(this._defaults.hasOwnProperty(setting)) {
                this._settings[setting] = this._defaults[setting];
            }
        }

        this._triggerObservers(setting, this._settings[setting]);
        return this._settings[setting];
    }

    /**
     * Register observer to get updated when a setting is changed
     *
     * @param settings
     * @returns {Promise<any>}
     */
    observe(settings, callback) {
        if(!Array.isArray(settings)) settings = [settings];

        for(let i = 0; i < settings.length; i++) {
            let setting = settings[i];
            if(!this._observers.hasOwnProperty(setting)) this._observers[setting] = [];
            this._observers[setting].push(callback);
        }
    }

    /**
     *
     */
    getAll() {
        return Utility.cloneObject(this._settings);
    }

    /**
     *
     */
    init() {
        let settings = document.querySelector('meta[name=settings]');
        if(settings) {
            this._addSettings(JSON.parse(settings.getAttribute('content')));
        }

        if(window.localStorage.hasOwnProperty('passwords.settings')) {
            this._addSettings(JSON.parse(window.localStorage.getItem('passwords.settings')));
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
        let [scope] = setting.split('.', 2);

        if(scope === 'local') {
            return SettingsManager._setLocalSetting(setting, value);
        } else if(scope === 'user' || scope === 'client') {
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
     * @param setting
     * @private
     */
    _resetLocalSetting(setting) {
        let settings = {};
        if(window.localStorage.hasOwnProperty('passwords.settings')) {
            settings = JSON.parse(window.localStorage.getItem('passwords.settings'));
        }

        if(settings.hasOwnProperty(setting)) {
            delete settings[setting];
            window.localStorage.setItem('passwords.settings', JSON.stringify(settings));
            return this._defaults[setting];
        }

        return null;
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

    /**
     * Trigger observers when a setting is changed
     *
     * @param setting
     * @param value
     * @returns {Promise<void>}
     * @private
     */
    async _triggerObservers(setting, value) {
        if(!this._observers.hasOwnProperty(setting)) return;

        for(let i = 0; i < this._observers[setting].length; i++) {
            this._observers[setting][i]({setting, value});
        }
    }
}

let SM = new SettingsManager();

export default SM;