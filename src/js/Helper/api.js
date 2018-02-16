import $ from "jquery";
import EnhancedApi from '@js/ApiClient/EnhancedApi';

class PwApi extends EnhancedApi {
    constructor() {
        if(location.protocol !== 'https:') {
            location.href = location.origin + location.pathname + '?https=false';
            return;
        }

        super(process.env.NODE_ENV !== 'production');

        this.isLoaded = false;
        this.loadInterval = setInterval(() => {this.initializePwApi()}, 10);
        $(window).on('load', () => {this.initializePwApi()});

    }

    initializePwApi() {
        if(!this.isLoaded && document.querySelector('meta[pwui-token]')) {
            this.isLoaded = true;
            clearInterval(this.loadInterval);

            let user = document.querySelector('head[data-user]').getAttribute('data-user');
            let password = document.querySelector('meta[pwui-token]').getAttribute('pwui-token');
            if(!password) password = prompt('Enter Nextcloud Password');

            let baseUrl = location.href.substr(0, location.href.indexOf('index.php'));
            this.login(baseUrl, user, password);
            window.initializePw();
        }
    }
}

const api = new PwApi();

export default api;