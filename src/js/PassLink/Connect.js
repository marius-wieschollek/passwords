import Vue from 'vue';

export default new class Connect {
    async initialize(hasLink = true, hasCode = true, protocol = 'ext+passlink') {
            let PassLinkDialog = await import(/* webpackChunkName: "ConnectClient" */ '@vue/Dialog/ConnectClient.vue'),
                ConnectDialog = Vue.extend(PassLinkDialog.default);

            new ConnectDialog({propsData: {hasLink, hasCode, protocol}}).$mount('#app-popup div');
    }
}