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

        if(features.hasOwnProperty(id)) {
            return process.env.NIGHTLY_FEATURES || features[id] === true;
        }

        return process.env.NIGHTLY_FEATURES;
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
                this._processFeatures(data);
            }
        } catch (e) {
            console.error(e);
        }

        return this._features;
    }

    /**
     *
     * @param json
     * @private
     */
    _processFeatures(json) {
        if (!json.hasOwnProperty(this._app)) return;
        let mainVersion = this._version.substr(0, this._version.lastIndexOf('.')),
            appFeatures = json[this._app];

        if (appFeatures.hasOwnProperty(mainVersion)) {
            this._features = appFeatures[mainVersion];
        }

        if (appFeatures.hasOwnProperty(this._version)) {
            let versionFeatures = appFeatures[this._version];

            for(let key in versionFeatures) {
                if(versionFeatures.hasOwnProperty(key)) {
                    this._features[key] = versionFeatures[key];
                }
            }
        }
    }
}

let DAS = new DeferredActivationService();

export default DAS;