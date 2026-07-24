<!--
  - @copyright 2023 Passwords App
  -
  - @author Marius David Wieschollek
  - @license AGPL-3.0
  -
  - This file is part of the Passwords App
  - created by Marius David Wieschollek.
  -->

<template>
    <div id="app-content" :class="getContentClass">
        <div class="app-content-left">
            <breadcrumb :showAddNew="isNotEmpty" :newPassword="false"/>
            <div class="item-list">
                <header-line :field="sorting.field" :ascending="sorting.ascending" v-on:updateSorting="updateSorting($event)" :is-trash-section="true" v-if="isNotEmpty"/>
                <folder-line :folder="folder" v-for="folder in folders" :key="folder.id">
                    <i class="icon fa fa-undo" slot="middle" @click.prevent="restoreFolderAction(folder)" :title="restoreTitle"></i>
                    <li slot="menu-top">
                        <translate tag="a" href="#" data-item-action="restore" icon="undo" @click.prevent="restoreFolderAction(folder)" say="Restore"/>
                    </li>
                </folder-line>
                <tag-line :tag="tag" v-for="tag in tags" :key="tag.id">
                    <i class="icon fa fa-undo" slot="middle" @click.prevent="restoreTagAction(tag)" :title="restoreTitle"></i>
                    <li slot="menu-top">
                        <translate tag="a" href="#" data-item-action="restore" icon="undo" @click.prevent="restoreTagAction(tag)" say="Restore"/>
                    </li>
                </tag-line>
                <password-line :password="password" v-for="password in passwords" v-if="password.trashed" v-on:restore="restorePasswordAction(password)" :key="password.id">
                    <template #custom-action>
                        <nc-action-button @click.stop.prevent="restorePasswordAction(password)" v-if="password.trashed">
                            <template #icon>
                                <restore-icon :size="20"/>
                            </template>
                            {{ t('Restore') }}
                        </nc-action-button>
                    </template>
                </password-line>
                <footer-line :passwords="passwords" :folders="folders" :tags="tags" v-if="isNotEmpty"/>
                <empty v-if="isEmpty" :text="getEmptyText"/>
            </div>
        </div>
    </div>
</template>

<script>
    import API from '@js/Helper/api';
    import Translate from '@vc/Translate';
    import Breadcrumb from '@vc/Breadcrumb';
    import FolderLine from '@vue/Components/ContentList/Item/Folder';
    import HeaderLine from '@vue/Components/ContentList/Item/Header';
    import FooterLine from '@vue/Components/ContentList/Item/Footer';
    import PasswordLine from '@vue/Components/ContentList/Item/Password';
    import TagManager from '@js/Manager/TagManager';
    import BaseSection from '@vue/Section/BaseSection';
    import FolderManager from '@js/Manager/FolderManager';
    import PasswordManager from '@js/Manager/PasswordManager';
    import LocalisationService from "@js/Services/LocalisationService";
    import RestoreIcon from "vue-material-design-icons/Restore.vue";
    import NcActionButton from "@nextcloud/vue/components/NcActionButton";

    export default {
        extends: BaseSection,

        components: {
            Translate,
            Breadcrumb,
            FolderLine,
            HeaderLine,
            FooterLine,
            PasswordLine,
            RestoreIcon,
            NcActionButton,
            'empty'   : () => import(/* webpackChunkName: "EmptyContent" */ '@vc/Empty'),
            'tag-line': () => import(/* webpackChunkName: "TagLine" */ '@vue/Components/ContentList/Item/Tag')
        },

        computed: {
            restoreTitle() {
                return LocalisationService.translate('Restore this item');
            },
            getEmptyText() {
                if(this.search.active) {
                    return LocalisationService.translate('We could not find anything for "{query}"', {query: this.search.query});
                }

                return 'Deleted items will appear here';
            }
        },

        methods: {
            refreshView: function() {
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
            }
        }
    };
</script>