import Utility from '@js/Classes/Utility';
import Logger from "@js/Classes/Logger";

export default class AdminSettingsService {

    /**
     *
     * @returns {Promise}
     */
    getAll() {
        return this._createRequest('settings');
    }

    /**
     *
     * @param setting
     * @returns {Promise}
     */
    get(setting) {
        return this._createRequest(`settings/${setting}`);
    }

    /**
     *
     * @param setting
     * @param value
     * @returns {Promise}
     */
    set(setting, value) {
        return this._createRequest(`settings/${setting}`, {value}, 'PUT')
    }


    /**
     *
     * @param setting
     * @returns {Promise}
     */
    reset(setting) {
        return this._createRequest(`settings/${setting}`, null, 'DELETE')
    }


    /**
     *
     * @returns {Promise}
     */
    listCaches() {
        return this._createRequest('caches');
    }

    /**
     *
     * @param cache
     * @returns {Promise}
     */
    clearCache(cache) {
        return this._createRequest(`caches/${cache}`, null, 'DELETE')
    }

    // noinspection JSMethodCanBeStatic
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
        let options = {method, credentials: 'include'};

        options.headers = new Headers();
        options.headers.append('Accept', `application/json, text/plain, */*`);
        options.headers.append('requesttoken', OC.requestToken);

        if(data) {
            if(method === 'GET') options.method = 'POST';
            options.headers.append('Content-Type', 'application/json');
            options.body = JSON.stringify(data);
        }

        try {
            let url = Utility.generateUrl(`/apps/passwords/admin/${path}`),
                response = await fetch(new Request(url, options));
            return await AdminSettingsService._processResponse(response);
        } catch(e) {
            Logger.error('Request failed', e);
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
            Logger.error(response);
            throw new Error('Api request failed');
        }

        let contentType = response.headers.get('content-type');
        if(contentType && contentType.indexOf('application/json') !== -1) {
            return await response.json();
        } else {
            Logger.error('Invalid response format', response);
            throw new Error('Invalid response format');
        }
    }
}