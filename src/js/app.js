import Vue from 'vue';
import App from '@vue/App';
import API from '@js/Helper/api';
import router from '@js/Helper/router';
import EventEmitter from 'eventemitter3';
import SectionAll from '@vue/Section/All';
import Utility from '@js/Classes/Utility';
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

(function () {
    let isLoaded     = false,
        loadInterval = null,
        app          = null,
        events = new EventEmitter();

    function initApp() {
        let section = SettingsManager.get('client.ui.section.default');

        router.addRoutes(
            [
                {name: 'All', path: section === 'all' ? '/':'/all', param: [], components: {main: SectionAll}},
                {path: '*', redirect: {name: section.capitalize()}}
            ]
        );

        router.beforeEach((to, from, next) => {
            if (!API.isAuthorized && to.name !== 'Authorize') {
                let target = {name: to.name, path: to.path, hash: to.hash, params: to.params};
                target = btoa(JSON.stringify(target));
                next({name: 'Authorize', params: {target}});
            }
            next();
            if (to.name !== from.name && window.innerWidth < 660) {
                let app = document.getElementById('app');
                if(app) app.classList.remove('mobile-open');
            }
        });

        app = new Vue(App);
    }

    async function initApi() {
        let baseUrl    = Utility.generateUrl(),
            user       = document.querySelector('meta[name=api-user]').getAttribute('content'),
            password   = document.querySelector('meta[name=api-token]').getAttribute('content'),
            folderIcon = SettingsManager.get('server.theme.folder.icon');
        if (!password) password = await Messages.prompt('Password', 'Login', '', true);
        if(baseUrl.indexOf('index.php') !== -1) baseUrl = baseUrl.substr(0, baseUrl.indexOf('index.php'));

        API.initialize({baseUrl, user, password, folderIcon, encryption: new Encryption(), events});
    }

    async function load() {
        if (isLoaded || !document.querySelector('meta[name=api-user]')) return;
        clearInterval(loadInterval);
        isLoaded = true;

        SettingsManager.init();
        await initApi();
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

            if (current !== e.keyCode || e.ctrlKey || e.shiftKey || e.metaKey) {
                pointer = 0;
                return;
            }

            pointer++;
            if (pointer === code.length) {
                document.getElementById('searchbox').value = '';
                app.starChaser = true;
            }
        }, false);


        events.on('api.request.failed', async (e) => {
            if(e.id === 'f84f93d3') {
                let current = router.currentRoute,
                    target = {name: current.name, path: current.path, hash: current.hash, params: current.params};
                target = btoa(JSON.stringify(target));
                router.push({name: 'Authorize', params: {target}});

                Messages.notification('Authorisation Expired. Please log in')
            } else if(e.response) {
                if(e.response.status === 401) {
                    await Messages.alert('The session token is no longer valid. The app will now reload.', 'API Session Token expired');
                    location.reload();
                } else if(e.response.status === 500) {
                    Messages.notification('Internal Server Error during request');
                } else {
                    Messages.notification(`${e.response.status} - ${e.response.statusText}`);
                    console.log(e);
                }
            } else if(e.message) {
                Messages.notification(e.message);
                console.log(e);
            } else {
                console.log(e);
            }
        })
    }

    if (location.protocol !== 'https:') {
        location.href = `${location.origin}${location.pathname}?https=false`;
    } else if (isCompatibleBrowser()) {
        window.addEventListener('load', () => { load(); }, false);
        loadInterval = setInterval(() => { load(); }, 10);
    }
}());