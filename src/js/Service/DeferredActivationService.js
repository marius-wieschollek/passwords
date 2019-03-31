import SettingsManager from '@js/Manager/SettingsManager';

class DeferredActivationService {

    /**
     *
     */
    constructor() {
        this._version = process.env.APP_VERSION;
        this._app = process.env.APP_NAME;
        this._features = null;
    }

    /**
     *
     * @param id
     * @returns {Promise<boolean>}
     */
    async check(id) {
        let features = await this.getFeatures();

        if(features.hasOwnProperty(id)) return features[id] === true;
        return false;
    }

    /**
     *
     * @returns {Promise<object>}
     */
    async getFeatures() {
        if (this._features !== null) return this._features;

        let url = SettingsManager.get('server.handbook.url') + '_files/deferred-activation.json';
        this._features = {};

        try {
            let response = await fetch(new Request(url, {credentials:'omit', referrerPolicy: 'no-referrer'}));
            if (response.ok) {
                let data = await response.json();

                if (data.hasOwnProperty(this._app) && data[this._app].hasOwnProperty(this._version)) {
                    this._features = data[this._app][this._version];
                }
            }
        } catch (e) {
            console.error(e);
        }

        return this._features;
    }
}

let DAS = new DeferredActivationService();

export default DAS;