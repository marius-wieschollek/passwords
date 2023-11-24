import API from '@js/Helper/api';
import SettingsService from "@js/Services/SettingsService";

export default new class FaviconService {

    /**
     *
     */
    constructor() {
        this._favicons = {};
        this._requests = {};
    }

    /**
     * Return icon if in cache, otherwise return default icon
     *
     * @param {String} domain
     * @param {Number} size
     * @return {Promise<String>}
     */
    get(domain, size = 32) {
        if(this._favicons.hasOwnProperty(`${domain}_${size}`)) {
            return this._favicons[`${domain}_${size}`];
        }

        return SettingsService.get('server.theme.app.icon');
    }

    /**
     *
     * @param {String} domain
     * @param {Number} size
     * @return {Promise<String>}
     */
    async fetch(domain, size = 32) {
        if(this._favicons.hasOwnProperty(`${domain}_${size}`)) {
            return this._favicons[`${domain}_${size}`];
        }

        if(this._requests.hasOwnProperty(`${domain}_${size}`)) {
            await this._requests[`${domain}_${size}`];
            return this._favicons[`${domain}_${size}`];
        }

        let request = this._queueApiRequest(domain, size);

        return await request;
    }

    /**
     *
     * @param {String} domain
     * @param {Number} size
     * @return {Promise<*>}
     * @private
     */
    async _queueApiRequest(domain, size) {
        let promise = this._fetchFromApi(domain, size);

        this._requests[`${domain}_${size}`] = promise;

        return promise;
    }

    /**
     *
     * @param {String} domain
     * @param {Number} size
     * @return {Promise<*>}
     * @private
     */
    async _fetchFromApi(domain, size) {
        let fallbackIcon = SettingsService.get('server.theme.app.icon');
        try {
            /** @type {Blob} favicon **/
            let favicon = await API.getFavicon(domain, size);

            if(favicon.type.substr(0, 6) !== 'image/' || favicon.size < 1) {
                delete this._requests[`${domain}_${size}`];

                if(!this._favicons.hasOwnProperty(`${domain}_${size}`)) {
                    this._favicons[`${domain}_${size}`] = fallbackIcon;
                }
                return fallbackIcon;
            }

            this._favicons[`${domain}_${size}`] = window.URL.createObjectURL(favicon);
            delete this._requests[`${domain}_${size}`];

            return this._favicons[`${domain}_${size}`];
        } catch(e) {
            if(this._requests.hasOwnProperty(`${domain}_${size}`)) {
                delete this._requests[`${domain}_${size}`];
            }
            if(!this._favicons.hasOwnProperty(`${domain}_${size}`)) {
                this._favicons[`${domain}_${size}`] = fallbackIcon;
            }
            return fallbackIcon;
        }
    }
};