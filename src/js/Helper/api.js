import $ from "jquery";
import EnhancedApi from '@js/ApiClient/EnhancedApi';

class PwApi extends EnhancedApi {
    constructor(debug = false) {
        super(location.origin, null, null, debug);

        $(window).on('load', () => {
            let user = document.querySelector('head[data-user]').getAttribute('data-user');
            let password = document.querySelector('meta[pwui-token]').getAttribute('pwui-token');
            if(!password) password = prompt('Enter Nextcloud Password');

            this.login(location.origin + '/index.php/apps/passwords/', user, password);
        });
    }
}

const api = new PwApi(true);


export default api;