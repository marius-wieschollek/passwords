import Vue from 'vue';
import Localisation from "@/js/Classes/Localisation";
import SettingsManager from '@js/Manager/SettingsManager';
import DeferredActivationService from '@js/Service/DeferredActivationService';

class SetupManager {

    async run() {
        if(SettingsManager.get('client.setup.initialized', false)) return;
        if(await DeferredActivationService.check('firstrunwizard')) return;
        await Localisation.loadSection('tutorial');

        let SetupDialog = await import(/* webpackChunkName: "SetupWizard" */ '@vue/Dialog/SetupDialog.vue'),
            SetupWizard = Vue.extend(SetupDialog.default);

        new SetupWizard({}).$mount('#app-popup div');
    }
}

let SUM = new SetupManager();

export default SUM;