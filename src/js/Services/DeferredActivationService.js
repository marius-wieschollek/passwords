import { loadState } from '@nextcloud/initial-state'
import LoggingService from "@js/Services/LoggingService";
import SettingsService from '@js/Services/SettingsService';

class DeferredActivationService {

    /**
     *
     */
    constructor() {
        this._features = null;
    }

    /**
     *
     * @param id
     * @param ignoreNightly
     * @returns {Promise<boolean>}
     */
    async check(id, ignoreNightly = false) {
        if(!ignoreNightly && APP_NIGHTLY) return true;

        let features = await this.getFeatures();
        if(features.hasOwnProperty(id)) return features[id] === true;

        return false;
    }

    /**
     *
     * @returns {Promise<object>}
     */
    async getFeatures() {
        if(this._features !== null) return this._features;

        this._features = {};
        let features = loadState('passwords', 'features', null);
        if(features) {
            this._features = features;
            return this._features;
        }

        let url = SettingsService.get('server.handbook.url') + '_features/features-v1.json';

        try {
            let response = await fetch(new Request(url, {credentials: 'omit', referrerPolicy: 'no-referrer'}));
            if(response.ok) {
                let data = await response.json();
                this._processFeatures(data);
            }
        } catch(e) {
            LoggingService.error(e);
        }

        return this._features;
    }

    /**
     *
     * @param json
     * @private
     */
    _processFeatures(json) {
        if(!json.hasOwnProperty(APP_TYPE)) return;
        let appFeatures = json[APP_TYPE];

        if(appFeatures.hasOwnProperty(APP_FEATURE_VERSION)) {
            this._features = appFeatures[APP_FEATURE_VERSION];
        }

        if(appFeatures.hasOwnProperty(APP_VERSION)) {
            let versionFeatures = appFeatures[APP_VERSION];

            for(let key in versionFeatures) {
                if(versionFeatures.hasOwnProperty(key)) {
                    this._features[key] = versionFeatures[key];
                }
            }
        }
    }
}

export default new DeferredActivationService();