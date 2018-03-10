import Vue from 'vue';
import App from '@vue/App';
import API from '@js/Helper/api';
import router from '@js/Helper/router';
import SectionAll from '@vue/Section/All';
import Messages from '@js/Classes/Messages';
import SearchManager from "@/js/Manager/SearchManager";
import SettingsManager from "@/js/Manager/SettingsManager";
import EncryptionTestHelper from "@/js/Helper/EncryptionTestHelper";

/**
 * Set global webpack path
 *
 * @type {string}
 */
__webpack_public_path__ = oc_appswebroots.passwords + '/';

(function() {
    let isLoaded     = false,
        loadInterval = null;

    if(location.protocol !== 'https:') {
        location.href = location.origin + location.pathname + '?https=false';
    } else {
        window.addEventListener('load', () => { load(); }, false);
        loadInterval = setInterval(() => { load(); }, 10);
    }

    async function load() {
        if(isLoaded || !document.querySelector('meta[name=pwat]')) return;
        clearInterval(loadInterval);
        isLoaded = true;

        await initApi();
        SettingsManager.init();
        initApp();
        if(process.env.NIGHTLY_FEATURES) SearchManager.init();
        if(process.env.NIGHTLY_FEATURES) EncryptionTestHelper.initTests();
    }

    function initApp() {
        let section = SettingsManager.get('client.ui.section.default');

        router.addRoutes(
            [
                {name: "All", path: section === 'all' ? '/':'/all', param: [], components: {main: SectionAll}},
                {path: '*', redirect: {name: section.capitalize()}}
            ]
        );

        new Vue(App);
    }

    async function initApi() {
        let user     = document.querySelector('head[data-user]').getAttribute('data-user'),
            password = document.querySelector('meta[name=pwat]').getAttribute('content');
        if(!password) password = await Messages.prompt('Password', 'Login', '', true);

        let baseUrl = location.href;
        if(baseUrl.indexOf('index.php') !== -1) {
            baseUrl = baseUrl.substr(0, baseUrl.indexOf('index.php'));
        } else {
            baseUrl = baseUrl.substr(0, baseUrl.indexOf('apps/'));
        }
        API.login(baseUrl, user, password);
    }
})();