import Vue from 'vue';
import App from '@vue/App';
import API from '@js/Helper/api';
import router from '@js/Helper/router';
import SectionAll from '@vue/Section/All';
import Messages from '@js/Classes/Messages';
import SearchManager from '@js/Manager/SearchManager';
import SettingsManager from '@js/Manager/SettingsManager';

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

        app = new Vue(App);
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

    async function load() {
        if(isLoaded || !document.querySelector('meta[name=pwat]')) return;
        clearInterval(loadInterval);
        isLoaded = true;

        await initApi();
        SettingsManager.init();
        initApp();
        SearchManager.init();
        initEvents();
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

    if(location.protocol !== 'https:') {
        location.href = `${location.origin}${location.pathname}?https=false`;
    } else if(isCompatibleBrowser()) {
        window.addEventListener('load', () => { load(); }, false);
        loadInterval = setInterval(() => { load(); }, 10);
    }
}());