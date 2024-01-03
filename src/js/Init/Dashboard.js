/*
 * @copyright 2023 Passwords App
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the Passwords App
 * created by Marius David Wieschollek.
 */

import {loadState} from "@nextcloud/initial-state";
import Localisation from "@js/Classes/Localisation";
import UtilityService from "@js/Services/UtilityService";

export default new class Dashboard {

    get isAuthorized() {
        return (this._dependencies && this._dependencies.api.isAuthorized) || !this._loginRequired;
    }

    constructor() {
        this._app = null;
        this._loginRequired = true;
        this._dependencies = null;
    }

    /**
     *
     */
    init() {
        OCA.Dashboard.register(
            'passwords-widget',
            (el) => {
                this._loadDashboardWidget(el);
            });
    }


    async _loadDashboardWidget(el) {
        try {
            el.classList.add('loading');
            el.style.height = '100%';
            await this._loadDependencies();
            let api = await this._initApi();
            await this._initSettings();
            await this._initVue(api, el);
        } catch(e) {
            el.innerText = e.message;
        }
    }

    async _initVue(api, el) {
        this._dependencies.vue.mixin(
            {
                methods: {
                    t: (t, v) => { return Localisation.translate(t, v); }
                }
            }
        );

        const View = this._dependencies.vue.extend(this._dependencies.dashboardWidget);
        this._app = new View({propsData: {api}}).$mount(el);
    }

    async _initApi() {
        let baseUrl = UtilityService.generateUrl(),
            user    = loadState('passwords', 'api-user', null),
            token   = loadState('passwords', 'api-token', null);

        if(!user || !token) {
            throw new Error('Failed to obtain api credentials');
        }

        if(baseUrl.indexOf('index.php') !== -1) baseUrl = baseUrl.substr(0, baseUrl.indexOf('index.php'));

        this._dependencies.clientService.initialize(baseUrl, user, token);
        await this._checkLoginRequirement();

        return this._dependencies.api;
    }

    async _initSettings() {
        this._dependencies.settingsService.init();
    }

    /**
     * Check if the user needs to authenticate
     *
     * @private
     */
    async _checkLoginRequirement() {
        let impersonate  = loadState('passwords', 'impersonate', false),
            authenticate = loadState('passwords', 'authenticate', true);
        this._loginRequired = authenticate || impersonate;

        if(!this._loginRequired) {
            await this._dependencies.api.openSession({});
        }
    }

    async _loadDependencies() {
        if(this._dependencies !== null) {
            return;
        }

        let module = await import( /* webpackChunkName: "Dependencies" */ '@js/Helper/Dashboard/Dependencies');
        this._dependencies = new module.default();
    }
};