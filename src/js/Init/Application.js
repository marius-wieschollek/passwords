import Vue from 'vue';
import App from '@vue/App';
import API from '@js/Helper/api';
import router from '@js/Helper/router';
import EventEmitter from 'eventemitter3';
import SectionAll from '@vue/Section/All';
import Utility from '@js/Classes/Utility';
import Messages from '@js/Classes/Messages';
import EventManager from '@js/Manager/EventManager';
import AlertManager from '@js/Manager/AlertManager';
import SearchManager from '@js/Manager/SearchManager';
import SettingsService from '@js/Services/SettingsService';
import KeepAliveManager from '@js/Manager/KeepAliveManager';
import EncryptionTestHelper from '@js/Helper/EncryptionTestHelper';


class Application {

    /**
     * @return {Vue}
     */
    get app() {
        return this._app;
    }

    /**
     * @return {EventEmitter}
     */
    get events() {
        return this._events;
    }

    /**
     * @return {Boolean}
     */
    get loginRequired() {
        return this._loginRequired;
    }

    /**
     *
     * @param {Boolean} value
     */
    set loginRequired(value) {
        this._loginRequired = value;
    }

    constructor() {
        this._loaded = false;
        this._timer = null;
        this._app = null;
        this._loginRequired = true;
        this._events = new EventEmitter();
    }

    /**
     *
     */
    init() {
        window.addEventListener('DOMContentLoaded', () => { this._initApp(); }, {once: true, passive: true});
        this._timer = setInterval(() => { this._initApp(); }, 10);
    }

    /**
     *
     * @returns {Promise<void>}
     * @private
     */
    _initApp() {
        if(this._loaded || !document.querySelector('meta[name=pw-api-user]')) return;
        clearInterval(this._timer);
        this._loaded = true;
        this._initSettings();
        if(this._initApi()) {
            this._checkLoginRequirement();
            this._initVue();
            SearchManager.init();
            EventManager.init();
            KeepAliveManager.init();
            AlertManager.init();
            EncryptionTestHelper.initTests();
        }
    }

    // noinspection JSMethodCanBeStatic
    /**
     *
     * @private
     */
    _initSettings() {
        SettingsService.init();
        document.body.setAttribute('data-server-version', SettingsService.get('server.version'));

        let customBackground = SettingsService.get('server.theme.background').indexOf('/core/') === -1 ? 'true':'false';
        document.body.setAttribute('data-custom-background', customBackground);

        let customColor = SettingsService.get('server.theme.color.primary') === '#0082c9' ? 'false':'true';
        document.body.setAttribute('data-custom-color', customColor);

        document.body.style.setProperty('--pw-image-login-background', `url(${SettingsService.get('server.theme.background')})`);
        document.body.style.setProperty('--pw-image-logo-themed', `url(${SettingsService.get('server.theme.app.icon')})`);

        let appIcon = SettingsService.get('server.theme.color.text') === '#ffffff' ? 'app':'app-dark';
        document.body.style.setProperty('--pw-image-logo', `url(${OC.appswebroots.passwords}/img/${appIcon}.svg)`);
    }

    /**
     *
     * @returns {boolean}
     * @private
     */
    _initApi() {
        let baseUrl    = Utility.generateUrl(),
            userEl     = document.querySelector('meta[name=pw-api-user]'),
            tokenEl    = document.querySelector('meta[name=pw-api-token]'),
            user       = userEl ? userEl.getAttribute('content'):null,
            token      = tokenEl ? tokenEl.getAttribute('content'):null,
            cseMode    = SettingsService.get('user.encryption.cse') === 1 ? 'CSEv1r1':'none',
            folderIcon = SettingsService.get('server.theme.folder.icon');

        if(!user || !token) {
            Messages.alert('The app was unable to obtain the api access credentials.', 'Initialisation Error')
                .then(() => { location.reload(); });
            return false;
        }

        if(baseUrl.indexOf('index.php') !== -1) baseUrl = baseUrl.substr(0, baseUrl.indexOf('index.php'));

        API.initialize({baseUrl, user, password: token, folderIcon, cseMode, events: this._events});
        return true;
    }

    /**
     * Check if the user needs to authenticate
     *
     * @private
     */
    _checkLoginRequirement() {
        let impersonateEl  = document.querySelector('meta[name=pw-impersonate]'),
            authenticateEl = document.querySelector('meta[name=pw-authenticate]');

        if(authenticateEl && impersonateEl) {
            this._loginRequired = authenticateEl.getAttribute('content') === 'true' || impersonateEl.getAttribute('content') === 'true';
        }

        if(!this._loginRequired) {
            document.body.classList.remove('pw-auth-visible');
            document.body.classList.add('pw-auth-skipped');
            API.openSession({});
        }
    }

    /**
     *
     * @private
     */
    _initVue() {
        let section = SettingsService.get('client.ui.section.default');

        router.addRoutes(
            [
                {name: 'All', path: section === 'all' ? '/':'/all', param: [], components: {main: SectionAll}},
                {path: '*', redirect: {name: section.capitalize()}}
            ]
        );

        router.beforeEach((to, from, next) => {
            if(!API.isAuthorized && this._loginRequired && to.name !== 'Authorize') {
                let target = {name: to.name, path: to.path, hash: to.hash, params: to.params};
                target = btoa(JSON.stringify(target));
                next({name: 'Authorize', params: {target}});
            }
            next();
            if(to.name !== from.name && window.innerWidth < 768) {
                let app = document.getElementById('app');
                if(app) app.classList.remove('mobile-open');
            }
        });

        this._app = new Vue(App);
    }
}

export default new Application();