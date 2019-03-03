import Vue from 'vue';
import SettingsManager from '@js/Manager/SettingsManager';

class SetupManager {

    async run() {
        if(SettingsManager.get('client.setup.initialized', false)) return;

        let SetupDialog = await import(/* webpackChunkName: "SetupWizard" */ '@vue/Dialog/SetupDialog.vue'),
            SetupWizard = Vue.extend(SetupDialog.default);

        new SetupWizard({}).$mount('#app-popup div');
    }
}

let SUM = new SetupManager();

export default SUM;