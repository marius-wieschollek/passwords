<template>
    <div id="app-content" :class="{ 'show-details': showDetails, 'loading': loading }">
        <div class="app-content-left">
            <breadcrumb :deleteAll="true" :newPassword="false" v-on:deleteAll="clearTrash"/>
            <div class="item-list">
                <folder-line :folder="folder" v-for="folder in folders" :key="folder.id">
                    <translate tag="li" icon="undo" slot="menu-top" @click="restoreFolderAction(folder)">Restore</translate>
                </folder-line>
                <tag-line :tag="tag" v-for="tag in tags" :key="tag.id">
                    <translate tag="li" icon="undo" slot="menu-top" @click="restoreTagAction(tag)">Restore</translate>
                </tag-line>
                <password-line :password="password" v-for="password in passwords" v-if="password.trashed" :key="password.id">
                    <translate tag="li" icon="undo" slot="menu-top" @click="restorePasswordAction(password)">Restore</translate>
                </password-line>
            </div>
        </div>
        <div class="app-content-right">
            <password-details v-if="detail.type === 'password'" :password="detail.element"/>
        </div>
    </div>
</template>

<script>
    import TagLine from '@vue/Line/Tag';
    import Translate from '@vc/Translate';
    import Events from "@js/Classes/Events";
    import Breadcrumb from '@vc/Breadcrumbs';
    import Utility from "@js/Classes/Utility";
    import FolderLine from '@vue/Line/Folder';
    import Messages from "@js/Classes/Messages";
    import PasswordLine from '@vue/Line/Password';
    import TagManager from '@js/Manager/TagManager';
    import PasswordDetails from '@vue/Details/Password';
    import FolderManager from '@js/Manager/FolderManager';
    import PasswordManager from '@js/Manager/PasswordManager';
    import API from '@js/Helper/api';

    export default {
        data() {
            return {
                loading  : true,
                tags     : [],
                folders  : [],
                passwords: [],
                detail   : {
                    type   : 'none',
                    element: null
                }
            };
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
            Events.off('data.changed', this.refreshView);
        },

        computed: {
            showDetails() {
                return this.detail.type !== 'none';
            }
        },

        methods: {
            refreshView: function() {
                API.findPasswords({trashed: true}).then(this.updatePasswordList);
                API.findFolders({trashed: true}).then(this.updateFolderList);
                API.findTags({trashed: true}).then(this.updateTagList);
            },

            updatePasswordList: function(passwords) {
                this.loading = false;
                this.passwords = Utility.sortApiObjectArray(passwords, 'label');
            },

            updateFolderList: function(folders) {
                this.loading = false;
                this.folders = Utility.sortApiObjectArray(folders, 'label');
            },

            updateTagList: function(tags) {
                this.loading = false;
                this.tags = Utility.sortApiObjectArray(tags, 'label');
            },

            restorePasswordAction(password) {
                PasswordManager.restorePassword(password);
                API.findPasswords({trashed: true}).then(this.updatePasswordList);
            },

            restoreFolderAction(folder) {
                FolderManager.restoreFolder(folder);
                API.findFolders({trashed: true}).then(this.updateFolderList);
            },

            restoreTagAction(tag) {
                TagManager.restoreTag(tag);
                API.findTags({trashed: true}).then(this.updateTagList);
            },

            clearTrash() {
                Messages.confirm('Delete all items in trash?', 'Empty Trash')
                        .then(() => {
                            for(let i = 0; i < this.passwords.length; i++) {
                                PasswordManager.deletePassword(this.passwords[i], false);
                            }
                            for(let i = 0; i < this.folders.length; i++) {
                                FolderManager.deleteFolder(this.folders[i], false);
                            }
                            for(let i = 0; i < this.tags.length; i++) {
                                TagManager.deleteTag(this.tags[i], false);
                            }

                            Messages.notification('Trash emptied');
                        });
            }
        }
    };
</script>