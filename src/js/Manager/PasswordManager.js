import Vue from 'vue';
import API from '@js/Helper/api';
import Events from '@js/Classes/Events';
import Utility from '@js/Classes/Utility';
import Messages from '@js/Classes/Messages';
import EnhancedApi from '@js/ApiClient/EnhancedApi';

/**
 *
 */
class PasswordManager {

    /**
     *
     * @param folder
     * @param tag
     * @returns {Promise}
     */
    createPassword(folder = null, tag = null) {
        return new Promise(async (resolve, reject) => {
            let properties = {},
                _success   = (p) => {
                    this.createPasswordFromData(p)
                        .then(resolve)
                        .catch(reject);
                };

            if(folder) properties.folder = folder;
            if(tag) properties.tags = [{id: tag}];

            let PasswordDialog = await import(/* webpackChunkName: "CreatePassword" */ '@vue/Dialog/CreatePassword.vue'),
                PwCreateDialog = Vue.extend(PasswordDialog.default);

            new PwCreateDialog({propsData: {properties,_success}}).$mount('#app-popup div');
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
                   password.editable = true;
                   password.revision = data.revision;
                   password.edited = password.created = password.updated = Utility.getTimestamp();
                   if(!password.label) EnhancedApi._generatePasswordTitle(password);
                   password = API._processPassword(password);
                   Events.fire('password.created', password);
               })
               .catch(() => {
                   Messages.notification('Creating password failed');
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
        return new Promise(async (resolve, reject) => {
            let propsData      = {properties: Utility.cloneObject(password), title: 'Edit password'},
                PasswordDialog = await import(/* webpackChunkName: "CreatePassword" */ '@vue/Dialog/CreatePassword.vue'),
                PwCreateDialog = Vue.extend(PasswordDialog.default),
                DialogWindow   = new PwCreateDialog({propsData}).$mount('#app-popup div');

            DialogWindow._success = (p) => {
                p = Utility.mergeObject(password, p);
                if(!p.label) EnhancedApi._generatePasswordTitle(p);
                if(password.password !== p.password) {
                    p.edited = new Date();
                } else {
                    p.edited = password.edited;
                }

                API.updatePassword(p)
                   .then((d) => {
                       p.revision = d.revision;
                       p.updated = new Date();
                       if(password.hasOwnProperty('tags')) p.tags = password.tags;
                       Events.fire('password.updated', p);
                       Messages.notification('Password saved');
                       resolve(p);
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
            if(password.id === folder || password.folder === folder) {
                reject(password);
                return;
            }

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
            if(!confirm || !password.trashed) {
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
                        .then(() => { this.deletePassword(password, false); })
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
            if(password.trashed) {
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

    /**
     *
     * @param password
     * @param revision
     * @param confirm
     * @returns {Promise<any>}
     */
    restoreRevision(password, revision, confirm = true) {
        return new Promise((resolve, reject) => {
            if(password.revision === revision.id) reject(password);

            if(!confirm) {
                API.restorePassword(password.id, revision.id)
                   .then((d) => {
                       password = Utility.mergeObject(password, revision);
                       password.id = d.id;
                       password.updated = new Date();
                       password.revision = d.revision;
                       Events.fire('password.restored', password);
                       Messages.notification('Revision restored');
                       resolve(password);
                   })
                   .catch(() => {
                       Messages.notification('Restoring revision failed');
                       reject(password);
                   });
            } else {
                Messages.confirm('Do you want to restore the revision?', 'Restore revision')
                        .then(() => { this.restoreRevision(password, revision, false); })
                        .catch(() => {reject(password);});
            }
        });
    }
}

let PM = new PasswordManager();

export default PM;