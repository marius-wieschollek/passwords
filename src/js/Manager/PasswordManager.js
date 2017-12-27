import Vue from 'vue';
import API from '@js/Helper/api';
import Events from '@js/Classes/Events';
import Utility from "@/js/Classes/Utility";
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

            if (folder) DialogWindow.password.folder = folder;
            DialogWindow._success = (p) => {
                this.createPasswordFromData(p)
                    .then(resolve)
                    .catch(reject);
            };
            DialogWindow._fail = reject;
        });
    }

    /**
     *
     * @param password
     * @returns {Promise<any>}
     */
    createPasswordFromData(password) {
        return new Promise((resolve, reject) => {
            API.createPassword(password)
                .then((data) => {
                    Messages.notification('Password created');

                    password.id = data.id;
                    password.status = 0;
                    password.revision = data.revision;
                    password.created = password.updated = Utility.getTimestamp();
                    password = API._processPassword(password);
                    Events.fire('password.created', password);
                })
                .catch(() => {
                    Messages.notification('Creating Password Failed');
                    reject(password);
                });
        });
    }

    /**
     *
     * @param password
     * @returns {Promise}
     */
    editPassword(password) {
        return new Promise((resolve, reject) => {
            let PwCreateDialog = Vue.extend(CreateDialog);
            let DialogWindow = new PwCreateDialog().$mount('#app-popup div');

            DialogWindow.title = 'Edit password';
            DialogWindow.password = password;
            DialogWindow._success = (p) => {
                API.updatePassword(password)
                    .then((d) => {
                        password.revision = d.revision;
                        password.updated = new Date();
                        Events.fire('password.updated', password);
                        Messages.notification('Password saved');
                        resolve(password);
                    })
                    .catch(() => {
                        Messages.notification('Saving password failed');
                        reject(password);
                    });
            };
            DialogWindow._fail = reject;
        });
    }

    movePassword(password, folder) {
        return new Promise((resolve, reject) => {
            if (password.id === folder) reject(password);

            let originalFolder = password.folder;
            password.folder = folder;
            API.updatePassword(password)
                .then((d) => {
                    password.revision = d.revision;
                    password.updated = new Date();
                    Events.fire('password.updated', password);
                    Messages.notification('Password moved');
                    resolve(password);
                })
                .catch(() => {
                    Messages.notification('Moving password failed');
                    password.folder = originalFolder;
                    reject(password);
                });
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
                    password.updated = new Date();
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
     * @param confirm
     * @returns {Promise}
     */
    deletePassword(password, confirm = true) {
        return new Promise((resolve, reject) => {
            if (!confirm || !password.trashed) {
                API.deletePassword(password.id)
                    .then((d) => {
                        password.trashed = true;
                        password.updated = new Date();
                        password.revision = d.revision;
                        Events.fire('password.deleted', password);
                        Messages.notification('Password deleted');
                        resolve(password);
                    })
                    .catch(() => {
                        Messages.notification('Deleting password failed');
                        reject(password);
                    });
            } else {
                Messages.confirm('Do you want to delete the password', 'Delete password')
                    .then(() => { this.deletePassword(true); })
                    .catch(() => {reject(password);});
            }
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
                        Messages.notification('Password restored');
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