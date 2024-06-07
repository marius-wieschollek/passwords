import {loadState} from '@nextcloud/initial-state';
import LoggingService from "@js/Services/LoggingService";
import SettingsService from '@js/Services/SettingsService';

class DeferredActivationService {

    /**
     *
     */
    constructor() {
        this._features = null;
        this.loadFeatures()
            .catch(LoggingService.catch);
    }

    /**
     *
     * @param {string} id
     * @param {boolean} ignoreNightly
     * @param {boolean} defaultValue
     * @returns {boolean}
     */
    check(id, ignoreNightly = false, defaultValue = false) {
        if(!ignoreNightly && APP_NIGHTLY) return true;

        let features = this.getFeatures();
        if(features.hasOwnProperty(id)) return features[id] === true;

        return defaultValue;
    }

    /**
     *
     * @returns {Object}
     */
    getFeatures() {
        if(this._features !== null) return this._features;
        return {};
    }

    /**
     *
     * @return {null}
     */
    async loadFeatures() {
        this._features = {};
        let features = loadState('passwords', 'features', null);
        if(features) {
            this._features = features;
            return;
        }

        try {
            let url      = SettingsService.get('server.handbook.url') + '_features/features-v1.json',
                options  = {credentials: 'omit', referrerPolicy: 'no-referrer'},
                response = await fetch(new Request(url, options), options);

            if(response.ok) {
                let data = await response.json();
                this._processFeatures(data);
            }
        } catch(e) {
            LoggingService.error(e);
        }
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