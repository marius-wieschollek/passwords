/*
 * @copyright 2023 Passwords App
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the Passwords App
 * created by Marius David Wieschollek.
 */

import Utility from "@js/Classes/Utility";
import {loadState} from "@nextcloud/initial-state";

export default new class Dashboard {

    constructor() {
        this._loaded = false;
        this._timer = null;
        this._api = null;
        this._app = null;
    }

    /**
     *
     */
    init() {
        window.addEventListener('DOMContentLoaded', () => { this._initApp(); }, {once: true, passive: true});
        this._timer = setInterval(() => { this._initApp(); }, 10);
    }

    _initApp() {
        if(this._loaded) return;
        this._loaded = true;
        clearInterval(this._timer);

        OCA.Dashboard.register(
            'passwords-widget',
            (el) => {
                this._loadDashboardWidget(el);
            });
    }

    _loadDashboardWidget(el) {
        this._initApi()
            .then(async (api) => {
                await this._initSettings();
                await this._initVue(api, el);
            })
            .catch((e) => {
                el.innerText = e.message;
            });
    }

    async _initVue(api, el) {
        let vue = await import( /* webpackChunkName: "Vue" */ "vue");
        let dashboardWidget = await import( /* webpackChunkName: "DashboardWidget" */ "@vue/Dashboard/Dashboard");

        const View = vue.default.extend(dashboardWidget.default);
        this._app = new View({propsData: {api}}).$mount(el);
    }

    async _initApi() {
        if(this._api !== null) {
            return this._api;
        }

        let baseUrl = Utility.generateUrl(),
            user    = loadState('passwords', 'api-user', null),
            token   = loadState('passwords', 'api-token', null);

        if(!user || !token) {
            throw new Error('Failed to obtain api credentials');
        }

        if(baseUrl.indexOf('index.php') !== -1) baseUrl = baseUrl.substr(0, baseUrl.indexOf('index.php'));
        let module = await import( /* webpackChunkName: "PasswordsAPI" */ '@js/Helper/api'),
            API    = module.default;

        API.initialize({baseUrl, user, password: token, encryption: {enabled: true, ready: () => { return true;}, unsetKeychain: () => {}}});
        API.openSession({});
        this._api = API;

        return API;
    }

    async _initSettings() {
        let module = await import( /* webpackChunkName: "SettingsService" */ '@js/Services/SettingsService');
        module.default.init();
    }
};