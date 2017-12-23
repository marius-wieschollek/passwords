import Vue from 'vue';
import API from '@js/Helper/api';
import Events from '@js/Classes/Events';
import Messages from '@js/Classes/Messages';
import EnhancedApi from "@/js/ApiClient/EnhancedApi";
import CreateDialog from '@vue/Dialog/CreatePassword.vue';

/**
 *
 */
class PasswordManager {

    /**
     *
     * @param folder
     * @returns {Promise}
     */
    createPassword(folder = null) {
        return new Promise((resolve, reject) => {
            let PwCreateDialog = Vue.extend(CreateDialog);
            let DialogWindow = new PwCreateDialog().$mount('#app-popup div');

            if(folder) DialogWindow.folder = folder;
        });
    }
}

let PM = new PasswordManager();

export default PM;