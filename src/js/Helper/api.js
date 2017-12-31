import EnhancedApi from '@js/ApiClient/EnhancedApi';

let token = 'passwords-webui-' + Math.random();

const api = new EnhancedApi(location.origin, null, null, token, true);

export default api;