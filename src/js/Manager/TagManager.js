import Vue from 'vue';
import API from '@js/Helper/api';
import Events from '@js/Classes/Events';
import Messages from '@js/Classes/Messages';
import EnhancedApi from "@/js/ApiClient/EnhancedApi";
import CreateDialog from '@vue/Dialog/CreatePassword.vue';

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
                type: 'text'
            },
            color: {
                label: 'Color',
                type: 'color'
            }
        };

        return new Promise((resolve, reject) => {
            Messages.form(form, 'Create tag')
                .then((tag) => {
                    tag = EnhancedApi.validateTag(tag);
                    tag.type = 'tag';
                    tag.created = new Date();
                    tag.updated = tag.created;

                    API.createTag(tag)
                        .then((d) => {
                            tag.id = d.id;
                            tag.revision = d.revision;
                            Events.fire('tag.created', tag);
                            Messages.notification('Tag created');
                            resolve(tag);
                        })
                        .catch(() => {
                            Messages.notification('Creating tag failed');
                            reject(tag);
                        });
                })
                .catch(() => {reject();})
        });
    }
}

let TM = new TagManager();

export default TM;