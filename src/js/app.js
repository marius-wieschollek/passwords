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
        isLoggedIn   = false;

    function initApp() {
        let section = SettingsManager.get('client.ui.section.default');

        router.addRoutes(
            [
                {name: 'All', path: section === 'all' ? '/':'/all', param: [], components: {main: SectionAll}},
                {path: '*', redirect: {name: section.capitalize()}}
            ]
        );

        router.beforeEach((to, from, next) => {
            if(!isLoggedIn && to.name !== 'Authorize') {
                let target = btoa(JSON.stringify(to));
                next({name:'Authorize', params: {target}});
                isLoggedIn = true;
            }
            next();
        });

        new Vue(App);
    }

    async function initApi() {
        let user     = document.querySelector('meta[name=api-user]').getAttribute('content'),
            password = document.querySelector('meta[name=api-token]').getAttribute('content');
        if(!password) password = await Messages.prompt('Password', 'Login', '', true);

        let baseUrl = location.href;
        if(baseUrl.indexOf('index.php') !== -1) {
            baseUrl = baseUrl.substr(0, baseUrl.indexOf('index.php'));
        } else {
            baseUrl = baseUrl.substr(0, baseUrl.indexOf('apps/'));
        }

        API.initialize({baseUrl, user, password, encryption: new Encryption(), debug: process.env.NODE_ENV !== 'production'});
    }

    async function load() {
        if(isLoaded || !document.querySelector('meta[name=api-user]')) return;
        clearInterval(loadInterval);
        isLoaded = true;

        await initApi();
        SettingsManager.init();
        initApp();
        SearchManager.init();
        EncryptionTestHelper.initTests();
    }

    if(location.protocol !== 'https:') {
        location.href = `${location.origin}${location.pathname}?https=false`;
    } else if(isCompatibleBrowser()) {
        window.addEventListener('load', () => { load(); }, false);
        loadInterval = setInterval(() => { load(); }, 10);
    }
}());