import $ from "jquery";
import EnhancedApi from '@js/ApiClient/EnhancedApi';
import EncryptionTestHelper from '@js/Helper/EncryptionTestHelper';

class PwApi extends EnhancedApi {
    constructor() {
        if(location.protocol !== 'https:') {
            location.href = location.origin + location.pathname + '?https=false';
            return;
        }

        super(process.env.NODE_ENV !== 'production');

        this.isLoaded = false;
        if(isCompatibleBrowser()) {
            this.loadInterval = setInterval(() => {this.initializePwApi()}, 10);
            $(window).on('load', () => {this.initializePwApi()});
        }

    }

    initializePwApi() {
        if(!this.isLoaded && document.querySelector('meta[pwui-token]')) {
            this.isLoaded = true;
            clearInterval(this.loadInterval);

            let user = document.querySelector('head[data-user]').getAttribute('data-user');
            let password = document.querySelector('meta[pwui-token]').getAttribute('pwui-token');
            if(!password) password = prompt('Enter Nextcloud Password');

            let baseUrl = location.href;
            if(baseUrl.indexOf('index.php') !== -1) {
                baseUrl = baseUrl.substr(0, baseUrl.indexOf('index.php'));
            } else {
                baseUrl = baseUrl.substr(0, baseUrl.indexOf('apps/'));
            }
            this.login(baseUrl, user, password);
            window.initializePw();
            if(this.debug) EncryptionTestHelper.initTests();
        }
    }
}

const api = new PwApi();

export default api;