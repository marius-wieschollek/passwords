import EnhancedApi from '@js/ApiClient/EnhancedApi';

let password = prompt('Enter Nextcloud Password');

const api = new EnhancedApi(location.origin, 'marius', password, true);

export default api;