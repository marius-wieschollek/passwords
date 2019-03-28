import Vue from 'vue';
import SettingsManager from '@js/Manager/SettingsManager';
import Localisation from "@/js/Classes/Localisation";

class SetupManager {

    async run() {
        if(SettingsManager.get('client.setup.initialized', false)) return;
        await Localisation.loadSection('tutorial');

        let SetupDialog = await import(/* webpackChunkName: "SetupWizard" */ '@vue/Dialog/SetupDialog.vue'),
            SetupWizard = Vue.extend(SetupDialog.default);

        new SetupWizard({}).$mount('#app-popup div');
    }
}

let SUM = new SetupManager();

export default SUM;