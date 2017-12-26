import API from '@js/Helper/api';
import Events from '@js/Classes/Events';
import Messages from '@js/Classes/Messages';
import EnhancedApi from "@/js/ApiClient/EnhancedApi";
import * as randomMC from "random-material-color";

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
                label: 'Title',
                type : 'text'
            },
            color: {
                label: 'Color',
                type : 'color',
                value: randomMC.getColor()
            }
        };

        return new Promise((resolve, reject) => {
            Messages.form(form, 'Create tag')
                .then((tag) => {
                    this.createTagFromData(tag)
                        .then(resolve)
                        .catch(reject);
                })
                .catch(() => {reject();})
        });
    }

    /**
     *
     * @param tag
     * @returns {Promise<any>}
     */
    createTagFromData(tag) {
        console.log(tag);
        if (!tag.label) tag.label = 'New Tag';
        if (!tag.color) tag.color = randomMC.getColor();
        tag = EnhancedApi.validateTag(tag);
        tag.type = 'tag';
        tag.created = new Date();
        tag.updated = tag.created;

        return new Promise((resolve, reject) => {
            API.createTag(tag)
                .then((d) => {
                    tag.id = d.id;
                    tag.revision = d.revision;
                    Events.fire('tag.created', tag);
                    Messages.notification('Tag created');
                    resolve(tag);
                })
                .catch((d) => {
                    console.log(d);
                    Messages.notification('Creating tag failed');
                    reject(tag);
                });
        });
    }

    editTag(tag) {
        let form = {
            label: {
                label: 'Title',
                type : 'text',
                value: tag.label
            },
            color: {
                label: 'Color',
                type : 'color',
                value: tag.color
            }
        };

        return new Promise((resolve, reject) => {
            Messages.form(form, 'Edit tag')
                .then((data) => {
                    tag.label = data.label;
                    tag.color = data.color;
                    tag = EnhancedApi.validateTag(tag);
                    tag.type = 'tag';
                    tag.updated = new Date();

                    API.updateTag(tag)
                        .then((d) => {
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
                .catch(() => {reject();})
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
                    tag.revision = d.revision;
                    Events.fire('folder.updated', tag);
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
            if (!confirm || !tag.trashed) {
                API.deleteTag(tag.id)
                    .then((d) => {
                        tag.trashed = true;
                        tag.revision = d.revision;
                        Events.fire('tag.deleted', tag);
                        Messages.notification('Tag was deleted');
                        resolve(tag);
                    })
                    .catch(() => {
                        Messages.notification('Deleting tag failed');
                        reject(tag);
                    });
            } else {
                Messages.confirm('Do you want to delete the tag', 'Delete tag')
                    .then(() => { this.deleteTag(true); })
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
            if (tag.trashed) {
                API.restoreTag(tag.id)
                    .then((d) => {
                        tag.trashed = false;
                        tag.revision = d.revision;
                        Events.fire('tag.restored', tag);
                        Messages.notification('Tag was restored');
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
}

let TM = new TagManager();

export default TM;