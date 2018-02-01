import $ from "jquery";
import EnhancedApi from '@js/ApiClient/EnhancedApi';

class PwApi extends EnhancedApi {
    constructor(debug = false) {
        super(location.origin, null, null, debug);

        this.count = 0;
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

            this.login(location.origin + '/index.php/apps/passwords/', user, password);
            window.initializePw();
        }
    }
}

const api = new PwApi(true);

export default api;