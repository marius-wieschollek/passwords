import Vue from 'vue';
import SettingsService from '@js/Services/SettingsService';
import UtilityService from "@js/Services/UtilityService";
import LoggingService from "@js/Services/LoggingService";
import LocalisationService from "@js/Services/LocalisationService";
import DeferredActivationService from "@js/Services/DeferredActivationService";

class SetupManager {

    /**
     * Check if wizard should be executed and run it
     *
     * @returns {Promise<void>}
     */
    async runAutomatically() {
        if(SettingsService.get('client.setup.initialized', false)) return;
        if(!DeferredActivationService.check('first-run-wizard', true)) return;

        await this._runWizard();
    }

    /**
     * Run the entire wizard manually
     *
     * @returns {Promise<void>}
     */
    runManually() {
        return this._runWizard();
    }

    /**
     * Run the encryption setup
     *
     * @returns {Promise<void>}
     */
    runEncryptionSetup() {
        try {
            return this._runWizard(['encryption'], true, false);
        } catch(e) {
            LoggingService.error(e);
        }
    }

    /**
     * Run the wizard with the given settings
     *
     * @param enableSlides
     * @param closable
     * @param redirect
     * @returns {Promise<void>}
     * @private
     */
    _runWizard(enableSlides, closable, redirect) {
        return new Promise(async (resolve) => {
            await LocalisationService.loadSection('tutorial');

            let SetupDialog = await import(/* webpackChunkName: "SetupWizard" */ '@vue/Dialog/SetupDialog.vue'),
                SetupWizard = Vue.extend(SetupDialog.default);

            new SetupWizard({propsData: {enableSlides, closable, redirect, _close: resolve}}).$mount(UtilityService.popupContainer());
        });
    }
}

export default new SetupManager();