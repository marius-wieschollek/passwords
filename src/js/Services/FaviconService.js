import SettingsService from "@js/Services/SettingsService";
import ClientService from "@js/Services/ClientService";

export default new class FaviconService {

    /**
     *
     */
    constructor() {
        this._service = null;
    }

    /**
     * Return icon if in cache, otherwise return default icon
     *
     * @param {String} domain
     * @param {Number} size
     * @return {String}
     */
    get(domain, size = 32) {
        return this._getService().get(domain, size);
    }

    /**
     *
     * @param {String} domain
     * @param {Number} size
     * @return {Promise<String>}
     */
    fetch(domain, size = 32) {
        return this._getService().fetch(domain, size);
    }

    /**
     *
     * @return {FaviconService}
     * @private
     */
    _getService() {
        if(this._service === null) {
            let fallbackIcon = SettingsService.get('server.theme.app.icon');

            /** @type FaviconService **/
            this._service = ClientService.getClient().getInstance('service.favicon', fallbackIcon);
        }

        return this._service;
    }
};