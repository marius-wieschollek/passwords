import Vue from 'vue';
import App from '@vue/App';
import API from '@js/Helper/api';
import router from '@js/Helper/router';
import EventEmitter from 'eventemitter3';
import SectionAll from '@vue/Section/All';
import Utility from '@js/Classes/Utility';
import Messages from '@js/Classes/Messages';
import EventManager from '@js/Manager/EventManager';
import SearchManager from '@js/Manager/SearchManager';
import SettingsService from '@js/Services/SettingsService';
import KeepAliveManager from '@js/Manager/KeepAliveManager';
import EncryptionTestHelper from '@js/Helper/EncryptionTestHelper';


class Application {

    get app() {
        return this._app;
    }

    get events() {
        return this._events;
    }

    get loginRequired() {
        return this._loginRequired;
    }

    set loginRequired(value) {
        return this._loginRequired = value;
    }

    constructor() {
        this._loaded = false;
        this._timer = null;
        this._app = null;
        this._loginRequired = true;
        this._events = new EventEmitter();
    }

    init() {
        window.addEventListener('load', () => { this._initApp(); }, {once: true, passive: true});
        this._timer = setInterval(() => { this._initApp(); }, 10);
    }

    /**
     *
     * @returns {Promise<void>}
     * @private
     */
    _initApp() {
        if(this._loaded || !document.querySelector('meta[name=api-user]')) return;
        clearInterval(this._timer);
        this._loaded = true;
        this._initSettings();
        this._initApi();
        this._checkLoginRequirement();
        this._initVue();
        SearchManager.init();
        EventManager.init();
        KeepAliveManager.init();
        EncryptionTestHelper.initTests();
    }

    // noinspection JSMethodCanBeStatic
    /**
     *
     * @private
     */
    _initSettings() {
        SettingsService.init();
        document.body.style.setProperty('--pw-image-login-background', `url(${SettingsService.get('server.theme.background')})`);
    }

    /**
     *
     * @returns {Promise<void>}
     * @private
     */
    _initApi() {
        let baseUrl    = Utility.generateUrl(),
            user       = document.querySelector('meta[name=api-user]').getAttribute('content'),
            token      = document.querySelector('meta[name=api-token]').getAttribute('content'),
            cseMode    = SettingsService.get('user.encryption.cse') === 1 ? 'CSEv1r1':'none',
            folderIcon = SettingsService.get('server.theme.folder.icon');

        if(!token) {
            Messages.alert('The app was unable to obtain the api access credentials.', 'Initialisation Error')
                .then(() => { location.reload(); });
            return;
        }

        if(baseUrl.indexOf('index.php') !== -1) baseUrl = baseUrl.substr(0, baseUrl.indexOf('index.php'));

        API.initialize({baseUrl, user, password: token, folderIcon, cseMode, events: this._events});
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
            document.body.classList.remove('pw-authorize');
            document.body.classList.add('pw-no-authorize');
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
            if(to.name !== from.name && window.innerWidth < 660) {
                let app = document.getElementById('app');
                if(app) app.classList.remove('mobile-open');
            }
        });

        this._app = new Vue(App);
    }
}

export default new Application();