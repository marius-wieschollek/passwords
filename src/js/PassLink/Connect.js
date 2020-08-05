import Vue from 'vue';

export default new class Connect {
    async initialize(showConnectLink = true) {
            let PassLinkDialog = await import(/* webpackChunkName: "ConnectClient" */ '@vue/Dialog/ConnectClient.vue'),
                ConnectDialog = Vue.extend(PassLinkDialog.default);

            new ConnectDialog({propsData: {showConnectLink}}).$mount('#app-popup div');
    }
}