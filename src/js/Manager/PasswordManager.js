import Vue from 'vue';
import API from '@js/Helper/api';
import Events from '@js/Classes/Events';
import Utility from '@js/Classes/Utility';
import Messages from '@js/Classes/Messages';

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

            new PwCreateDialog({propsData: {properties, _success}}).$mount('#app-popup div');
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
                .then(async (data) => {
                    Messages.notification('Password created');

                    if(password.hasOwnProperty('tags')) {
                        let model = await API.showPassword(data.id, '+tags');
                        password.tags = model.tags;
                    }

                    password.id = data.id;
                    password.status = 0;
                    password.editable = true;
                    password.revision = data.revision;
                    password.edited = password.created = password.updated = Utility.getTimestamp();
                    if(!password.label) API._generatePasswordTitle(password);
                    password = API._processPassword(password);
                    Events.fire('password.created', password);
                })
                .catch((e) => {
                    console.error(e);
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
                if(!p.label) API._generatePasswordTitle(p);
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
                        if(typeof p.customFields === 'string') p.customFields = JSON.parse(p.customFields);

                        Events.fire('password.updated', p);
                        Messages.notification('Password saved');
                        resolve(p);
                    })
                    .catch((e) => {
                        console.error(e);
                        Messages.notification('Saving password failed');
                        reject(password);
                    });
            };
            DialogWindow._fail = reject;
        });
    }

    /**
     *
     * @param password
     * @return {Promise<unknown>}
     */
    clonePassword(password) {
        return new Promise(async (resolve, reject) => {
            let properties = Utility.cloneObject(password);
            properties.id = null;
            properties.status = null;
            properties.revision = null;
            properties.password = null;
            properties.updated = null;
            properties.created = null;
            properties.edited = null;

            for(let customField of properties.customFields) {
                if(customField.type === 'secret') {
                    customField.value = '';
                }
            }

            if(!properties.hasOwnProperty('tags')) {
                let model = await API.showPassword(password.id, '+tags');
                properties.tags = model.tags;
            }

            let _success = (p) => {
                this.createPasswordFromData(p)
                    .then(resolve)
                    .catch(reject);
            };

            let PasswordDialog = await import(/* webpackChunkName: "CreatePassword" */ '@vue/Dialog/CreatePassword.vue'),
                PwCreateDialog = Vue.extend(PasswordDialog.default);

            new PwCreateDialog({propsData: {properties, _success}}).$mount('#app-popup div');
            PwCreateDialog._fail = reject;
        });
    }

    /**
     *
     * @param password
     * @param folder
     * @returns {Promise<any>}
     */
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
                .catch((e) => {
                    console.error(e);
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
                .catch((e) => {
                    console.error(e);
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
                API.deletePassword(password.id, password.revision)
                    .then((d) => {
                        password.trashed = true;
                        password.updated = new Date();
                        password.revision = d.revision;
                        Events.fire('password.deleted', password);
                        Messages.notification('Password deleted');
                        resolve(password);
                    })
                    .catch((e) => {
                        if(e.id && e.id === 'f281915e') {
                            password.trashed = true;
                            password.updated = new Date();
                            Events.fire('password.deleted', password);
                            resolve(password);
                        } else {
                            Messages.notification('Deleting password failed');
                            reject(password);
                        }
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
            if(password.revision === revision.id) reject(new Error('Revision is current revision'));

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
                    .catch((e) => {
                        Messages.notification('Restoring revision failed');
                        reject(e);
                    });
            } else {
                Messages.confirm('Do you want to restore the revision?', 'Restore revision')
                    .then(() => { this.restoreRevision(password, revision, false).then(resolve).catch(reject); })
                    .catch(() => {reject(new Error('User aborted revision restore'));});
            }
        });
    }

    // noinspection JSMethodCanBeStatic
    /**
     *
     * @param password
     * @param revision
     *
     * @returns {Promise}
     */
    async viewRevision(password, revision) {
        let RevisionDialog     = await import(/* webpackChunkName: "ViewRevision" */ '@vue/Dialog/ViewRevision.vue'),
            ViewRevisionDialog = Vue.extend(RevisionDialog.default);

        new ViewRevisionDialog({propsData: {password, revision}}).$mount('#app-popup div');
    }
}

export default new PasswordManager();