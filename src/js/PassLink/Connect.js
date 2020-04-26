import Vue from 'vue';

export default new class Connect {
    async initialize(folder = null, tag = null) {
            let PassLinkDialog = await import(/* webpackChunkName: "CreatePassword" */ '@vue/Dialog/ConnectClient.vue'),
                ConnectDialog = Vue.extend(PassLinkDialog.default);

            new ConnectDialog().$mount('#app-popup div');
    }
}