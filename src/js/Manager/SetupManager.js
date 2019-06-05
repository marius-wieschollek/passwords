import Vue from 'vue';
import Localisation from "@js/Classes/Localisation";
import SettingsService from '@js/Service/SettingsService';
import DeferredActivationService from '@js/Service/DeferredActivationService';

class SetupManager {

    /**
     * Check if wizard should be executed and run it
     *
     * @returns {Promise<void>}
     */
    async runAutomatically() {
        if(SettingsService.get('client.setup.initialized', false)) return;
        if(!await DeferredActivationService.check('first-run-wizard', true)) return;

        await this._runWizard();
    }

    /**
     * Run the entire wizard manually
     *
     * @returns {Promise<void>}
     */
    async runManually() {
        await this._runWizard();
    }

    /**
     * Run the encryption setup
     *
     * @returns {Promise<void>}
     */
    async runEncryptionSetup() {
        await this._runWizard(['start', 'encryption'], true, false);
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
    async _runWizard(enableSlides, closable, redirect) {
        return new Promise(async (resolve, reject) => {
            await Localisation.loadSection('tutorial');

            let SetupDialog = await import(/* webpackChunkName: "SetupWizard" */ '@vue/Dialog/SetupDialog.vue'),
                SetupWizard = Vue.extend(SetupDialog.default);

            new SetupWizard({propsData: {enableSlides, closable, redirect, _close:resolve}}).$mount('#app-popup div');
        });
    }
}

export default new SetupManager();