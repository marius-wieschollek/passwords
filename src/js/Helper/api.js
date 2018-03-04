import EnhancedApi from '@js/ApiClient/EnhancedApi';

class PwApi extends EnhancedApi {
    constructor() {
        super(process.env.NODE_ENV !== 'production');
    }
}

const api = new PwApi();

export default api;