import EnhancedApi from '@js/ApiClient/EnhancedApi';

let user = 'admin';
let password = prompt('Enter Nextcloud Password');

let api = {};
if(user && password) {
    api = new EnhancedApi(location.origin, user, password, true);
} else {
    alert('Authentication failure');
}

export default api;