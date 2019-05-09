import Vue from 'vue';
import Localisation from "@/js/Classes/Localisation";
import SettingsManager from '@js/Manager/SettingsManager';
import DeferredActivationService from '@js/Service/DeferredActivationService';

class SetupManager {

    /**
     * Check if wizard should be executed and run it
     *
     * @returns {Promise<void>}
     */
    async runAutomatically() {
        if(SettingsManager.get('client.setup.initialized', false)) return;
        if(await DeferredActivationService.check('first-run-wizard')) return;

        this._runWizard();
    }

    /**
     * Run the entire wizard manually
     *
     * @returns {Promise<void>}
     */
    async runManually() {
        this._runWizard();
    }

    /**
     * Run the encryption setup
     *
     * @returns {Promise<void>}
     */
    async runEncryptionSetup() {
        this._runWizard(['start', 'encryption'], true, false);
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
        await Localisation.loadSection('tutorial');

        let SetupDialog = await import(/* webpackChunkName: "SetupWizard" */ '@vue/Dialog/SetupDialog.vue'),
            SetupWizard = Vue.extend(SetupDialog.default);

        new SetupWizard({propsData: {enableSlides, closable, redirect}}).$mount('#app-popup div');
    }
}

let SUM = new SetupManager();

export default SUM;