import API from '@js/Helper/api';
import Events from '@js/Classes/Events';
import Messages from '@js/Classes/Messages';
import EnhancedApi from "@/js/ApiClient/EnhancedApi";

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
            Messages
                .prompt('Enter folder name', 'Create folder')
                .then((title) => {
                    let folder = {label: title};
                    if (parent) folder.parent = parent;

                    folder = EnhancedApi.validateFolder(folder);
                    folder.type = 'folder';
                    folder.icon = 'http://localhost/core/img/filetypes/folder.svg';
                    folder.created = new Date();
                    folder.updated = folder.created;
                    API.createFolder(folder)
                        .then((d) => {
                            folder.id = d.id;
                            folder.revision = d.revision;
                            Events.fire('folder.created', folder);
                            Messages.notification('Folder created');
                            resolve(folder);
                        })
                        .catch(() => {
                            Messages.notification('Creating folder failed');
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
            Messages
                .prompt('Enter folder name', 'Rename folder', folder.label)
                .then((title) => {
                    let originalTitle = folder.label;
                    folder.label = title;
                    API.updateFolder(folder)
                        .then((d) => {
                            folder.revision = d.revision;
                            Events.fire('folder.deleted', folder);
                            Messages.notification('Folder renamed');
                            resolve(folder);
                        })
                        .catch(() => {
                            Messages.notification('Renaming folder failed');
                            folder.label = originalTitle;
                            reject(folder);
                        });
                }).catch(() => {reject(folder);});
        });
    }

    moveFolder(folder, parent) {
        return new Promise((resolve, reject) => {
            if (folder.id === parent) reject(folder);

            let originalParent = folder.parent;
            folder.parent = parent;
            API.updateFolder(folder)
                .then((d) => {
                    folder.revision = d.revision;
                    Events.fire('folder.updated', folder);
                    Messages.notification('Folder moved');
                    resolve(folder);
                })
                .catch(() => {
                    Messages.notification('Moving folder failed');
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
            if (!confirm || !folder.trashed) {
                API.deleteFolder(folder.id)
                    .then((d) => {
                        folder.trashed = true;
                        folder.revision = d.revision;
                        Events.fire('folder.deleted', folder);
                        Messages.notification('Folder was deleted');
                        resolve(folder);
                    })
                    .catch(() => {
                        Messages.notification('Deleting folder failed');
                        reject(folder);
                    });
            } else {
                Messages.confirm('Do you want to delete the folder', 'Delete folder')
                    .then(() => { this.deleteFolder(true); })
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
            if (folder.trashed) {
                API.restorePassword(folder.id)
                    .then((d) => {
                        folder.trashed = false;
                        folder.revision = d.revision;
                        Events.fire('folder.restored', folder);
                        Messages.notification('Tag was restored');
                        resolve(folder);
                    })
                    .catch(() => {
                        Messages.notification('Restoring folder failed');
                        reject(folder);
                    });
            } else {
                reject(folder);
            }
        });
    }
}

let FM = new FolderManager();

export default FM;