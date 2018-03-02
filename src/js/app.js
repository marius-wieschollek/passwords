import Vue from 'vue';
import App from '@vue/App.vue';
import EncryptionTestHelper from "@/js/Helper/EncryptionTestHelper";

__webpack_public_path__ = oc_appswebroots.passwords + '/';
window.initializePw = () => {
    new Vue(App);
    if(process.env.NODE_ENV !== 'production') EncryptionTestHelper.initTests();
};