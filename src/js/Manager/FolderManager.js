import API from '@js/Helper/api';
import Events from '@js/Classes/Events';
import Utility from '@js/Classes/Utility';
import Vue from "vue";
import MessageService from "@js/Services/MessageService";
import ToastService from "@js/Services/ToastService";

/**
 *
 */
class FolderManager {

    /**
     *
     * @param parent
     * @returns {Promise<any>}
     */
    createFolder(parent = null) {
        return new Promise((resolve, reject) => {
            MessageService
                .prompt('Name', 'Create folder')
                .then((title) => {
                    let folder = {label: title};
                    if(parent) folder.parent = parent;

                    folder = API.validateFolder(folder);
                    API.createFolder(folder)
                       .then(async (d) => {
                           folder.id = d.id;
                           folder.revision = d.revision;
                           folder._encrypted = folder.cseKey?.length > 0;
                           folder.cseKey = d.cseKey;
                           folder.cseType = d.cseType;
                           folder.edited = folder.created = folder.updated = Utility.getTimestamp();
                           folder = await API._processFolder(folder);
                           Events.fire('folder.created', folder);
                           ToastService.success('Folder created');
                           resolve(folder);
                       })
                       .catch(() => {
                           ToastService.error('Creating folder failed');
                           reject(folder);
                       });
                }).catch(() => {reject();});
        });
    }

    /**
     *
     * @param folder
     * @returns {Promise}
     */
    renameFolder(folder) {
        return new Promise((resolve, reject) => {
            MessageService
                .prompt('Name', 'Rename folder', null, null, folder.label)
                .then((title) => {
                    let originalTitle = folder.label;
                    folder.label = title;
                    folder.edited = new Date();

                    API.updateFolder(folder)
                       .then((d) => {
                           folder.updated = new Date();
                           folder.revision = d.revision;
                           Events.fire('folder.deleted', folder);
                           ToastService.success('Folder renamed');
                           resolve(folder);
                       })
                       .catch(() => {
                           ToastService.error('Renaming folder failed');
                           folder.label = originalTitle;
                           reject(folder);
                       });
                }).catch(() => {reject(folder);});
        });
    }

    /**
     *
     * @param folder
     * @param parent
     * @returns {Promise<any>}
     */
    moveFolder(folder, parent = null) {
        return new Promise(async (resolve, reject) => {
            if(parent === null) {
                let parentModel
                try {
                    parentModel = await this.selectFolder(folder.parent, [folder.id]);
                } catch(e) {
                    reject(folder);
                }
                parent = parentModel.id;
                if(folder.hidden && !parentModel.hidden) folder.hidden = false;
            }
            if(folder.id === parent || folder.parent === parent || folder.parent.id === parent) {
                reject(folder);
                return;
            }

            let originalParent = folder.parent;
            folder.parent = parent;
            API.updateFolder(folder)
               .then((d) => {
                   folder.updated = new Date();
                   folder.revision = d.revision;
                   Events.fire('folder.updated', folder);
                   ToastService.info('Folder moved');
                   resolve(folder);
               })
               .catch(() => {
                   ToastService.error('Moving folder failed');
                   folder.parent = originalParent;
                   reject(folder);
               });
        });
    }

    /**
     *
     * @param folder
     * @returns {Promise}
     */
    updateFolder(folder) {
        return new Promise((resolve, reject) => {
            API.updateFolder(folder)
               .then((d) => {
                   folder.updated = new Date();
                   folder.revision = d.revision;
                   Events.fire('folder.updated', folder);
                   resolve(folder);
               })
               .catch(() => {
                   reject(folder);
               });
        });
    }

    /**
     *
     * @param folder
     * @param confirm
     * @returns {Promise}
     */
    deleteFolder(folder, confirm = true) {
        return new Promise((resolve, reject) => {
            if(!confirm || !folder.trashed) {
                API.deleteFolder(folder.id, folder.revision)
                   .then((d) => {
                       folder.trashed = true;
                       folder.updated = new Date();
                       folder.revision = d.revision;
                       Events.fire('folder.deleted', folder);
                       ToastService.info('Folder deleted');
                       resolve(folder);
                   })
                   .catch((e) => {
                       if(e.id && e.id === 'f281915e') {
                           folder.trashed = true;
                           folder.updated = new Date();
                           Events.fire('folder.deleted', folder);
                           resolve(folder);
                       } else {
                           ToastService.error('Deleting folder failed');
                           reject(folder);
                       }
                   });
            } else {
                MessageService.confirm('Do you want to delete the folder', 'Delete folder')
                        .then(() => { this.deleteFolder(folder, false); })
                        .catch(() => {reject(folder);});
            }
        });
    }

    /**
     *
     * @param folder
     * @returns {Promise}
     */
    restoreFolder(folder) {
        return new Promise((resolve, reject) => {
            if(folder.trashed) {
                API.restoreFolder(folder.id)
                   .then((d) => {
                       folder.trashed = false;
                       folder.updated = new Date();
                       folder.revision = d.revision;
                       Events.fire('folder.restored', folder);
                       ToastService.info('Folder restored');
                       resolve(folder);
                   })
                   .catch(() => {
                       ToastService.error('Restoring folder failed');
                       reject(folder);
                   });
            } else {
                reject(folder);
            }
        });
    }

    /**
     *
     * @param folder
     * @param revision
     * @param confirm
     * @returns {Promise<any>}
     */
    restoreRevision(folder, revision, confirm = true) {
        return new Promise((resolve, reject) => {
            if(folder.revision === revision.id) reject(folder);

            if(!confirm) {
                API.restorePassword(folder.id, revision.id)
                   .then((d) => {
                       folder = Utility.mergeObject(folder, revision);
                       folder.id = d.id;
                       folder.updated = new Date();
                       folder.revision = d.revision;
                       Events.fire('folder.restored', folder);
                       ToastService.info('Revision restored');
                       resolve(folder);
                   })
                   .catch(() => {
                       ToastService.error('Restoring revision failed');
                       reject(folder);
                   });
            } else {
                MessageService.confirm('Do you want to restore the revision?', 'Restore revision')
                        .then(() => { this.restoreRevision(folder, revision, false); })
                        .catch(() => {reject(folder);});
            }
        });
    }

    /**
     *
     * @param folder
     * @param ignoredFolders
     * @returns {Promise<(Object|null)>}
     */
    selectFolder(folder = '00000000-0000-0000-0000-000000000000', ignoredFolders = []) {
        if(typeof folder === 'object') folder = folder.id;

        return new Promise(async (resolve, reject) => {
            let FolderPicker       = await import(/* webpackChunkName: "FolderPicker" */ '@vue/Dialog/FolderPicker.vue'),
                FolderPickerDialog = Vue.extend(FolderPicker.default);

            new FolderPickerDialog({propsData: {folder, ignoredFolders, resolve, reject}}).$mount(Utility.popupContainer());
        });
    }
}

export default new FolderManager();