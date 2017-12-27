<template>
    <div id="app-content" v-bind:class="{ 'show-details': showDetails }">
        <div class="app-content-left">
            <breadcrumb :showAddNew="false" />
            <div class="item-list">
                <folder-line :folder="folder" v-for="folder in folders" :key="folder.id">
                    <translate tag="li" icon="undo" slot="option-top" @click="restoreFolderAction(folder)">Restore</translate>
                </folder-line>
                <tag-line :tag="tag" v-for="tag in tags" :key="tag.id">
                    <translate tag="li" icon="undo" slot="option-top" @click="restoreTagAction(tag)">Restore</translate>
                </tag-line>
                <password-line :password="password" v-for="password in passwords" v-if="password.trashed" :key="password.id">
                    <translate tag="li" icon="undo" slot="option-top" @click="restorePasswordAction(password)">Restore</translate>
                </password-line>
            </div>
        </div>
        <div class="app-content-right">
            <password-details v-if="detail.type === 'password'" :password="detail.element" />
        </div>
    </div>
</template>

<script>
    import Events from "@js/Classes/Events";
    import Utility from "@js/Classes/Utility";
    import Translate from '@vc/Translate.vue';
    import Breadcrumb from '@vc/Breadcrumbs.vue';
    import TagLine from '@vue/Line/Tag.vue';
    import FolderLine from '@vue/Line/Folder.vue';
    import PasswordLine from '@vue/Line/Password.vue';
    import PasswordDetails from '@vue/Details/Password.vue';
    import TagManager from '@js/Manager/TagManager';
    import FolderManager from '@js/Manager/FolderManager';
    import PasswordManager from '@js/Manager/PasswordManager';
    import API from '@js/Helper/api';

    export default {
        data() {
            return {
                tags: [],
                folders: [],
                passwords: [],
                detail   : {
                    type   : 'none',
                    element: null
                }
            }
        },

        components: {
            TagLine,
            Translate,
            Breadcrumb,
            FolderLine,
            PasswordLine,
            PasswordDetails
        },

        created() {
            this.refreshView();
            Events.on('data.changed', this.refreshView);
        },

        beforeDestroy() {
            Events.off('data.changed', this.refreshView)
        },

        computed: {
            showDetails() {
                return this.detail.type !== 'none';
            }
        },

        methods: {
            refreshView: function () {
                API.findPasswords({trashed:true}).then(this.updatePasswordList);
                API.findFolders({trashed:true}).then(this.updateFolderList);
                API.findTags({trashed:true}).then(this.updateTagList);
            },

            updatePasswordList: function (passwords) {
                this.passwords = Utility.sortApiObjectArray(passwords, 'label');
            },

            updateFolderList: function (folders) {
                this.folders = Utility.sortApiObjectArray(folders, 'label');
            },

            updateTagList: function (tags) {
                this.tags = Utility.sortApiObjectArray(tags, 'label');
            },

            restorePasswordAction(password) {
                PasswordManager.restorePassword(password);
                API.findPasswords({trashed:true}).then(this.updatePasswordList);
            },

            restoreFolderAction(folder) {
                FolderManager.restoreFolder(folder);
                API.findFolders({trashed:true}).then(this.updateFolderList);
            },

            restoreTagAction(tag) {
                TagManager.restoreTag(tag);
                API.findTags({trashed:true}).then(this.updateTagList);
            }
        }
    }
</script>