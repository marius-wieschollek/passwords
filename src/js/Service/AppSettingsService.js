
export default class AdminSettingsService {

    constructor() {
        this._baseUrl = AdminSettingsService._getBaseUrl();
    }

    /**
     *
     * @returns {string}
     * @private
     */
    static _getBaseUrl() {
        let baseUrl = location.href;

        if (baseUrl.indexOf('index.php') !== -1) {
            baseUrl = baseUrl.substr(0, baseUrl.indexOf('index.php'));
        } else if(baseUrl.indexOf('settings/') !== -1) {
            baseUrl = baseUrl.substr(0, baseUrl.indexOf('settings/'));
        } else {
            baseUrl = baseUrl.substr(0, baseUrl.indexOf('apps/'));
        }

        return baseUrl + 'index.php/apps/passwords/admin/';
    }

    getAll() {
        return this._createRequest('settings');
    }

    get(setting) {
        return this._createRequest(`settings/${setting}`);
    }

    set(setting, value) {
        return this._createRequest(`settings/${setting}`, {value}, 'PUT')
    }

    reset(setting) {
        return this._createRequest(`settings/${setting}`, null, 'DELETE')
    }


    listCaches() {
        return this._createRequest('caches');
    }

    clearCache(cache) {
        return this._createRequest(`caches/${cache}`, null, 'DELETE')
    }

    /**
     * Creates an api request
     *
     * @param path
     * @param data
     * @param method
     * @returns {Promise}
     * @private
     */
    async _createRequest(path, data = null, method = 'GET') {
        let options = {method};

        options.headers = new Headers();
        options.headers.append('Accept', `application/json, text/plain, */*`);
        options.headers.append('requesttoken', oc_requesttoken);

        if(data) {
            if(method === 'GET') options.method = 'POST';
            options.headers.append('Content-Type', 'application/json');
            options.body = JSON.stringify(data);
        }

        try {
            let response = await fetch(new Request(this._baseUrl + path, options));
            return await AdminSettingsService._processResponse(response);
        } catch(e) {
            console.error('Request failed', e);
            throw e;
        }
    }

    /**
     *
     * @param response
     * @returns {Promise<*>}
     * @private
     */
    static async _processResponse(response) {
        if(!response.ok) {
            console.error(response);
            throw new Error('Api request failed');
        }

        let contentType = response.headers.get('content-type');
        if(contentType && contentType.indexOf('application/json') !== -1) {
            return await response.json();
        } else {
            console.error('Invalid response format', response);
            throw new Error('Invalid response format');
        }
    }
}