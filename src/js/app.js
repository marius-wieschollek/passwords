import Vue from 'vue';
import App from '@vue/App';
import API from '@js/Helper/api';
import router from '@js/Helper/router';
import SectionAll from '@vue/Section/All';
import Messages from '@js/Classes/Messages';
import Encryption from '@js/ApiClient/Encryption';
import SearchManager from '@js/Manager/SearchManager';
import SettingsManager from '@js/Manager/SettingsManager';
import EncryptionTestHelper from '@js/Helper/EncryptionTestHelper';

/**
 * Set global webpack path
 *
 * @type {string}
 */
__webpack_public_path__ = `${oc_appswebroots.passwords}/`;

(function() {
    let isLoaded     = false,
        loadInterval = null,
        app          = null;

    function initApp() {
        let section = SettingsManager.get('client.ui.section.default');

        router.addRoutes(
            [
                {name: 'All', path: section === 'all' ? '/':'/all', param: [], components: {main: SectionAll}},
                {path: '*', redirect: {name: section.capitalize()}}
            ]
        );

        router.beforeEach((to, from, next) => {
            if(!API.isAuthorized && to.name !== 'Authorize') {
                let target = {name: to.name, path: to.path, hash: to.hash, params: to.params};
                target = btoa(JSON.stringify(target));
                next({name: 'Authorize', params: {target}});
            }
            next();
        });

        app = new Vue(App);
    }

    async function initApi() {
        let user     = document.querySelector('meta[name=api-user]').getAttribute('content'),
            password = document.querySelector('meta[name=api-token]').getAttribute('content'),
            session  = getSessionId(),
            baseUrl  = getBaseUrl();
        if(!password) password = await Messages.prompt('Password', 'Login', '', true);

        API.initialize({baseUrl, user, password, session, encryption: new Encryption(), debug: process.env.NODE_ENV !== 'production'});
    }

    async function load() {
        if(isLoaded || !document.querySelector('meta[name=api-user]')) return;
        clearInterval(loadInterval);
        isLoaded = true;

        await initApi();
        SettingsManager.init();
        initApp();
        SearchManager.init();
        initEvents();
        EncryptionTestHelper.initTests();
    }

    function initEvents() {
        let code    = [38, 38, 40, 40, 37, 39, 37, 39, 66, 65],
            pointer = 0;

        document.addEventListener('keyup', (e) => {
            let current = code[pointer];

            if(current !== e.keyCode || e.ctrlKey || e.shiftKey || e.metaKey) {
                pointer = 0;
                return;
            }

            pointer++;
            if(pointer === code.length) {
                document.getElementById('searchbox').value = '';
                app.starChaser = true;
            }
        }, false);
    }

    function getSessionId() {
        let sessionAttr = document.querySelector('meta[name=api-session]');

        if(sessionAttr) {
            let session = JSON.parse(sessionAttr.getAttribute('content'));
            isLoggedIn = session.authorized;

            return session.id;
        }

        return null;
    }

    function getBaseUrl() {
        let baseUrl = location.href;

        if(baseUrl.indexOf('index.php') !== -1) {
            baseUrl = baseUrl.substr(0, baseUrl.indexOf('index.php'));
        } else {
            baseUrl = baseUrl.substr(0, baseUrl.indexOf('apps/'));
        }

        return baseUrl;
    }

    if(location.protocol !== 'https:') {
        location.href = `${location.origin}${location.pathname}?https=false`;
    } else if(isCompatibleBrowser()) {
        window.addEventListener('load', () => { load(); }, false);
        loadInterval = setInterval(() => { load(); }, 10);
    }
}());