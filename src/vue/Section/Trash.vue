<template>
    <div id="app-content" :class="getContentClass">
        <div class="app-content-left">
            <breadcrumb :showAddNew="isNotEmpty" :deleteAll="true" :restoreAll="true" :newPassword="false" v-on:deleteAll="clearTrash" v-on:restoreAll="restoreTrash"/>
            <div class="item-list">
                <header-line :field="sorting.field" :ascending="sorting.ascending" v-on:updateSorting="updateSorting($event)" v-if="isNotEmpty"/>
                <folder-line :folder="folder" v-for="folder in folders" :key="folder.id">
                    <i class="icon fa fa-undo" slot="middle" @click="restorePasswordAction(password)" :title="restoreTitle"></i>
                    <translate tag="li" icon="undo" slot="menu-top" @click="restoreFolderAction(folder)" say="Restore"/>
                </folder-line>
                <tag-line :tag="tag" v-for="tag in tags" :key="tag.id">
                    <i class="icon fa fa-undo" slot="middle" @click="restorePasswordAction(password)" :title="restoreTitle"></i>
                    <translate tag="li" icon="undo" slot="menu-top" @click="restoreTagAction(tag)" say="Restore"/>
                </tag-line>
                <password-line :password="password" v-for="password in passwords" v-if="password.trashed" :key="password.id">
                    <i class="icon fa fa-undo" slot="middle" @click="restorePasswordAction(password)" :title="restoreTitle"></i>
                    <translate tag="li" icon="undo" slot="menu-top" @click="restorePasswordAction(password)" say="Restore"/>
                </password-line>
                <footer-line :passwords="passwords" :folders="folders" :tags="tags" v-if="isNotEmpty"/>
                <empty v-if="isEmpty" :text="getEmptyText"/>
            </div>
        </div>
        <div class="app-content-right">
            <password-details v-if="showPasswordDetails" :password="detail.element"/>
        </div>
    </div>
</template>

<script>
    import API from '@js/Helper/api';
    import TagLine from '@vue/Line/Tag';
    import Translate from '@vc/Translate';
    import Breadcrumb from '@vc/Breadcrumb';
    import FolderLine from '@vue/Line/Folder';
    import HeaderLine from '@vue/Line/Header';
    import FooterLine from '@vue/Line/Footer';
    import Empty from '@vue/Components/Empty';
    import Messages from '@js/Classes/Messages';
    import PasswordLine from '@vue/Line/Password';
    import TagManager from '@js/Manager/TagManager';
    import BaseSection from '@vue/Section/BaseSection';
    import PasswordDetails from '@vue/Details/Password';
    import Localisation from '@/js/Classes/Localisation';
    import FolderManager from '@js/Manager/FolderManager';
    import PasswordManager from '@js/Manager/PasswordManager';

    export default {
        extends: BaseSection,

        components: {
            Empty,
            TagLine,
            Translate,
            Breadcrumb,
            FolderLine,
            HeaderLine,
            FooterLine,
            PasswordLine,
            PasswordDetails
        },

        computed: {
            restoreTitle() {
                return Localisation.translate('Restore this item');
            },
            getEmptyText() {
                if(this.search.active) {
                    return Localisation.translate('We could not find anything for "{query}"', {query:this.search.query});
                }

                return 'Deleted items will appear here';
            },
        },

        methods: {
            refreshView       : function() {
                let model = this.ui.showTags ? 'model+tags':'model';
                API.findPasswords({trashed: true}, model).then(this.updatePasswordList);
                API.findFolders({trashed: true}).then(this.updateFolderList);
                API.findTags({trashed: true}).then(this.updateTagList);
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
            restoreTrash() {
                Messages.confirm('Restore all items in trash?', 'Restore Items')
                        .then(() => {
                            for(let i = 0; i < this.passwords.length; i++) {
                                PasswordManager.restorePassword(this.passwords[i]);
                            }
                            for(let i = 0; i < this.folders.length; i++) {
                                FolderManager.restoreFolder(this.folders[i]);
                            }
                            for(let i = 0; i < this.tags.length; i++) {
                                TagManager.restoreTag(this.tags[i]);
                            }

                            Messages.notification('Items restored');
                        });
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