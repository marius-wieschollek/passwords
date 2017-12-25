import Vue from 'vue';
import API from '@js/Helper/api';
import Events from '@js/Classes/Events';
import Messages from '@js/Classes/Messages';
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

            if (folder) DialogWindow.folder = folder;
        });
    }

    /**
     *
     * @param password
     * @returns {Promise}
     */
    updatePassword(password) {
        return new Promise((resolve, reject) => {
            API.updatePassword(password)
                .then((d) => {
                    password.revision = d.revision;
                    Events.fire('password.updated', password);
                    resolve(password);
                })
                .catch(() => {
                    reject(password);
                });
        });
    }

    /**
     *
     * @param password
     * @returns {Promise}
     */
    restorePassword(password) {
        return new Promise((resolve, reject) => {
            if (password.trashed) {
                API.restorePassword(password.id)
                    .then((d) => {
                        password.trashed = false;
                        password.revision = d.revision;
                        Events.fire('password.restored', password);
                        Messages.notification('Tag was restored');
                        resolve(password);
                    })
                    .catch(() => {
                        Messages.notification('Restoring password failed');
                        reject(password);
                    });
            } else {
                reject(password);
            }
        });
    }
}

let PM = new PasswordManager();

export default PM;