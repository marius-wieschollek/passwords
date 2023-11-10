/*
 * @copyright 2023 Passwords App
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the Passwords App
 * created by Marius David Wieschollek.
 */

import Vue from "vue";
import API from "@js/Helper/api";
import Utility from "@js/Classes/Utility";
import {loadState} from "@nextcloud/initial-state";
import DashboardVue from "@vue/Dashboard/Dashboard";
import SettingsService from "@js/Services/SettingsService";

export default new class Dashboard {

    constructor(props) {
        this._loaded = false;
        this._timer = null;
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

        if(this._initApi()) {
            SettingsService.init();
            this._initVue();
        }
    }

    _initApi() {
        let baseUrl = Utility.generateUrl(),
            user    = loadState('passwords', 'api-user', null),
            token   = loadState('passwords', 'api-token', null);

        if(!user || !token) {
            this._error('Failed to obtain api credentials');
            return false;
        }

        if(baseUrl.indexOf('index.php') !== -1) baseUrl = baseUrl.substr(0, baseUrl.indexOf('index.php'));
        API.initialize({baseUrl, user, password: token, encryption: {enabled: true, ready: () => { return true}, unsetKeychain: () => {}}});
        API.openSession({});

        return true;
    }

    _initVue() {
        OCA.Dashboard.register(
            'passwords-widget',
            (el) => {
                const View = Vue.extend(DashboardVue);
                const vm = new View({propsData: {}}).$mount(el);
            });
    }

    _error(message) {
        OCA.Dashboard.register(
            'passwords-widget',
            (el) => {
                el.innerText = message;
            });
    }
};