import API from '@js/Helper/api';
import Utility from '@js/Classes/Utility';
import {loadState} from '@nextcloud/initial-state';
import Logger from "@js/Classes/Logger";
import DatabaseService from "./DatabaseService";

/**
 *
 */
class SettingsService {

    constructor() {
        this._defaults = {
            'local.ui.sort.ascending'            : true,
            'local.ui.sort.field'                : 'label',
            'local.sharing.qrcode.warning'       : true,
            'local.encryption.webauthn.enabled'  : false,
            'client.ui.section.default'          : 'folders',
            'client.ui.password.field.title'     : 'label',
            'client.ui.password.field.sorting'   : 'byTitle',
            'client.ui.password.click.action'    : 'password',
            'client.ui.password.dblClick.action' : 'username',
            'client.ui.password.wheel.action'    : 'open-url',
            'client.ui.password.custom.action'   : 'none',
            'client.ui.password.menu.copy'       : false,
            'client.ui.password.user.show'       : false,
            'client.ui.password.details.preview' : true,
            'client.ui.password.details.advanced': false,
            'client.ui.password.print'           : false,
            'client.ui.custom.fields.show.hidden': false,
            'client.ui.list.tags.show'           : false,
            'client.setup.initialized'           : false,
            'client.search.show'                 : false,
            'client.search.live'                 : true,
            'client.search.global'               : true,
            'client.settings.advanced'           : false,
            'client.session.keepalive'           : 0
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
        this._triggerObservers(setting, value);
        this._setSetting(setting, value).then((realValue) => {
            if(realValue !== value) {
                this._settings[setting] = realValue;
                this._triggerObservers(setting, realValue);
            }
        });
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
            this._settings[setting] = await this._resetLocalSetting(setting);
        } else if(scope === 'user' || scope === 'client') {
            this._settings[setting] = await API.resetSetting(setting);
            if(this._defaults.hasOwnProperty(setting)) {
                this._settings[setting] = this._defaults[setting];
            }
        }

        this._triggerObservers(setting, this._settings[setting])
            .catch(Logger.exception);
        return this._settings[setting];
    }

    /**
     * Register observer to get updated when a setting is changed
     *
     * @param {(String|String[])} settings
     * @param {Function} callback
     * @returns {Promise<any>}
     */
    observe(settings, callback) {
        if(!Array.isArray(settings)) settings = [settings];

        for(let setting of settings) {
            if(!this._observers.hasOwnProperty(setting)) this._observers[setting] = [];
            this._observers[setting].push(callback);
        }
    }

    /**
     * Remove an observer for the given settings
     *
     * @param {(String|String[])} settings
     * @param {Function} callback
     */
    unobserve(settings, callback) {
        if(!Array.isArray(settings)) settings = [settings];

        for(let setting of settings) {
            if(!this._observers.hasOwnProperty(setting)) continue;
            let index = this._observers[setting].indexOf(callback);
            while(index !== -1) {
                this._observers[setting].splice(index, 1);
                index = this._observers[setting].indexOf(callback);
            }
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
    async init() {
        let settings = loadState('passwords', 'settings', null);
        if(settings) {
            this._addSettings(settings);
        }
        await this._migrateOldLocalSettings();
        this._addSettings(
            await DatabaseService.getAll(DatabaseService.SETTINGS_TABLE)
        );
    }

    async _migrateOldLocalSettings() {
        let localSettingsKey = 'passwords.settings.' + loadState('passwords', 'api-user', null);
        if(window.localStorage.hasOwnProperty('passwords.settings')) {
            window.localStorage.setItem(
                localSettingsKey,
                window.localStorage.getItem('passwords.settings')
            );
            window.localStorage.removeItem('passwords.settings');
        }
        if(window.localStorage.hasOwnProperty(localSettingsKey)) {
            let data = JSON.parse(window.localStorage.getItem(localSettingsKey));
            for(let key in data) {
                await DatabaseService.set(DatabaseService.SETTINGS_TABLE, key, data[key]);
            }
            window.localStorage.removeItem('passwords.settings');
        }
    }

    /**
     *
     * @param setting
     * @param value
     * @returns {Promise<*>}
     * @private
     */
    async _setSetting(setting, value) {
        let [scope] = setting.split('.', 2);

        if(scope === 'local') {
            return await this._setLocalSetting(setting, value);
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
    _setLocalSetting(setting, value) {
        return DatabaseService.set(DatabaseService.SETTINGS_TABLE, setting, value);
    }

    /**
     *
     * @param setting
     * @private
     */
    _resetLocalSetting(setting) {
        return DatabaseService.remove(DatabaseService.SETTINGS_TABLE, setting);
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

export default new SettingsService();