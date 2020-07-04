import API from '@js/Helper/api';
import SettingsService from "@js/Services/SettingsService";

export default new class FaviconService {

    constructor() {
        this._favicons = {};
        this._requests = {};
    }

    /**
     *
     * @param {String} domain
     * @param {Number} size
     * @return {Promise<String>}
     */
    async fetch(domain, size = 32) {
        if(this._favicons.hasOwnProperty(domain) && this._favicons[domain].hasOwnProperty(size)) {
            return this._favicons[domain][size];
        }

        if(this._requests.hasOwnProperty(domain) && this._requests[domain].hasOwnProperty(size)) {
            await this._requests[domain][size];
            return this._favicons[domain][size];
        }

        let request = this._fetchFromApi(domain, size);
        if(!this._requests.hasOwnProperty(domain)) this._requests[domain] = {};
        this._requests[domain][size] = request;

        return await request;
    }

    /**
     *
     * @param {String} domain
     * @param {Number} size
     * @return {Promise<*>}
     * @private
     */
    async _fetchFromApi(domain, size) {
        try {
            /** @type Blob favicon **/
            let favicon = await API.getFavicon(domain, size);
            if(!this._favicons.hasOwnProperty(domain)) this._favicons[domain] = {};
            this._favicons[domain][size] = window.URL.createObjectURL(favicon);
            delete this._requests[domain][size];

            return this._favicons[domain][size];
        } catch(e) {
            if(this._requests.hasOwnProperty(domain) && this._requests[domain].hasOwnProperty(size)) {
                delete this._requests[domain][size];
            }
            return SettingsService.get('server.theme.app.icon');
        }
    }
};