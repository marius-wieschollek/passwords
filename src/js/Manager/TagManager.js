import API from '@js/Helper/api';
import Events from '@js/Classes/Events';
import Utility from '@js/Classes/Utility';
import Messages from '@js/Classes/Messages';
import Localisation from '@js/Classes/Localisation';
import RandomColorService from '@js/Services/RandomColorService';

/**
 *
 */
class TagManager {

    /**
     *
     * @returns {Promise}
     */
    createTag() {
        let form = {
            label: {
                label   : 'Name',
                type    : 'text',
                required: true
            },
            color: {
                type  : 'color',
                value : RandomColorService.color(),
                button: {
                    icon  : 'refresh',
                    title : 'Generate random color',
                    action: () => { return RandomColorService.color();}
                }
            }
        };

        return new Promise((resolve, reject) => {
            Messages.form(form, 'Create tag')
                .then((tag) => {
                    this.createTagFromData(tag)
                        .then(resolve)
                        .catch(reject);
                })
                .catch(reject);
        });
    }

    /**
     *
     * @param tag
     * @returns {Promise<any>}
     */
    createTagFromData(tag) {
        if(!tag.label) tag.label = Localisation.translate('New Tag');
        if(!tag.color) tag.color = RandomColorService.color();
        tag = API.validateTag(tag);

        return new Promise((resolve, reject) => {
            API.createTag(tag)
                .then(async (d) => {
                    tag.id = d.id;
                    tag.revision = d.revision;
                    tag._encrypted = tag.cseKey?.length > 0;
                    tag.cseKey = d.cseKey;
                    tag.cseType = d.cseType;
                    tag.edited = tag.created = tag.updated = Utility.getTimestamp();
                    tag = await API._processTag(tag);
                    Events.fire('tag.created', tag);
                    Messages.notification('Tag created');
                    resolve(tag);
                })
                .catch(() => {
                    Messages.notification('Creating tag failed');
                    reject(tag);
                });
        });
    }

    /**
     *
     * @param tag
     * @returns {Promise<any>}
     */
    editTag(tag) {
        let form = {
            label: {
                label   : 'Name',
                type    : 'text',
                value   : tag.label,
                required: true
            },
            color: {
                type  : 'color',
                value : tag.color,
                button: {
                    icon  : 'refresh',
                    title : 'Generate random color',
                    action: () => { return RandomColorService.color(); }
                }
            }
        };

        return new Promise((resolve, reject) => {
            Messages.form(form, 'Edit tag')
                .then((data) => {
                    tag.label = data.label;
                    tag.color = data.color;
                    tag.edited = new Date();

                    API.updateTag(tag)
                        .then((d) => {
                            tag.updated = new Date();
                            tag.revision = d.revision;
                            Events.fire('tag.updated', tag);
                            Messages.notification('Tag saved');
                            resolve(tag);
                        })
                        .catch(() => {
                            Messages.notification('Saving tag failed');
                            reject(tag);
                        });
                })
                .catch(() => {reject();});
        });
    }

    /**
     *
     * @param tag
     * @returns {Promise}
     */
    updateTag(tag) {
        return new Promise((resolve, reject) => {
            API.updateTag(tag)
                .then((d) => {
                    tag.updated = new Date();
                    tag.revision = d.revision;
                    Events.fire('tag.updated', tag);
                    resolve(tag);
                })
                .catch(() => {
                    reject(tag);
                });
        });
    }

    /**
     *
     * @param tag
     * @param confirm
     * @returns {Promise}
     */
    deleteTag(tag, confirm = true) {
        return new Promise((resolve, reject) => {
            if(!confirm || !tag.trashed) {
                API.deleteTag(tag.id, tag.revision)
                    .then((d) => {
                        tag.trashed = true;
                        tag.updated = new Date();
                        tag.revision = d.revision;
                        Events.fire('tag.deleted', tag);
                        Messages.notification('Tag deleted');
                        resolve(tag);
                    })
                    .catch((e) => {
                        if(e.id && e.id === 'f281915e') {
                            tag.trashed = true;
                            tag.updated = new Date();
                            Events.fire('tag.deleted', tag);
                            resolve(tag);
                        } else {
                            Messages.notification('Deleting tag failed');
                            reject(tag);
                        }
                    });
            } else {
                Messages.confirm('Do you want to delete the tag', 'Delete tag')
                    .then(() => { this.deleteTag(tag, false); })
                    .catch(() => {reject(tag);});
            }
        });
    }

    /**
     *
     * @param tag
     * @returns {Promise}
     */
    restoreTag(tag) {
        return new Promise((resolve, reject) => {
            if(tag.trashed) {
                API.restoreTag(tag.id)
                    .then((d) => {
                        tag.trashed = false;
                        tag.updated = new Date();
                        tag.revision = d.revision;
                        Events.fire('tag.restored', tag);
                        Messages.notification('Tag restored');
                        resolve(tag);
                    })
                    .catch(() => {
                        Messages.notification('Restoring tag failed');
                        reject(tag);
                    });
            } else {
                reject(tag);
            }
        });
    }

    /**
     *
     * @param tag
     * @param revision
     * @param confirm
     * @returns {Promise<any>}
     */
    restoreRevision(tag, revision, confirm = true) {
        return new Promise((resolve, reject) => {
            if(tag.revision === revision.id) reject(tag);

            if(!confirm) {
                API.restoreTag(tag.id, revision.id)
                    .then((d) => {
                        tag = Utility.mergeObject(tag, revision);
                        tag.id = d.id;
                        tag.updated = new Date();
                        tag.revision = d.revision;
                        Events.fire('tag.restored', tag);
                        Messages.notification('Revision restored');
                        resolve(tag);
                    })
                    .catch(() => {
                        Messages.notification('Restoring revision failed');
                        reject(tag);
                    });
            } else {
                Messages.confirm('Do you want to restore the revision?', 'Restore revision')
                    .then(() => { this.restoreRevision(tag, revision, false); })
                    .catch(() => {reject(tag);});
            }
        });
    }
}

export default new TagManager();