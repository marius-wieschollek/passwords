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
            <breadcrumb
                    :showAddNew="getBreadcrumb.showAddNew"
                    :newPassword="getBreadcrumb.newPassword"
                    :newFolder="getBreadcrumb.newFolder"
                    :newTag="getBreadcrumb.newTag"
                    :folder="getBreadcrumb.folder"
                    :tag="getBreadcrumb.tag"
                    :items="getBreadcrumb.items"/>
            <div class="item-list" :class="{'context-menu': contextMenu}" :style="style">
                <header-line :field="sorting.field"
                             :ascending="sorting.ascending"
                             v-on:updateSorting="updateSorting($event)"
                             v-if="isNotEmpty"/>
                <folder-line :folder="folder" v-for="folder in folders" :key="folder.id" :draggable="isDraggable" v-if="!loading"/>
                <tag-line :tag="tag" v-for="tag in tags" :key="tag.id" :draggable="isDraggable" v-if="!loading"/>
                <password-line :password="password"
                               v-for="password in passwords"
                               :key="password.id"
                               :draggable="isDraggable" v-if="!loading"/>
                <footer-line :passwords="passwords" :folders="folders" :tags="tags" v-if="isNotEmpty"/>
                <empty v-if="isEmpty" :text="getEmptyText"/>
            </div>
        </div>
    </div>
</template>

<script>
    import Breadcrumb from '@vc/Breadcrumb';
    import Events from '@js/Classes/Events';
    import FolderLine from '@vue/Components/ContentList/Item/Folder';
    import FooterLine from '@vue/Components/ContentList/Item/Footer';
    import PasswordLine from '@vue/Components/ContentList/Item/Password';
    import SearchManager from '@js/Manager/SearchManager';
    import BatchActionManager from '@js/Manager/BatchActionManager';
    import UtilityService from "@js/Services/UtilityService";
    import SettingsService from '@js/Services/SettingsService';
    import LocalisationService from "@js/Services/LocalisationService";
    import {subscribe, unsubscribe} from "@nextcloud/event-bus";

    export default {
        components: {
            Breadcrumb,
            FolderLine,
            FooterLine,
            PasswordLine,
            'header-line': () => import(/* webpackChunkName: "HeaderLine" */ '@vue/Components/ContentList/Item/Header'),
            'empty'      : () => import(/* webpackChunkName: "EmptyContent" */ '@vc/Empty'),
            'tag-line'   : () => import(/* webpackChunkName: "TagLine" */ '@vue/Components/ContentList/Item/Tag')
        },

        data() {
            return {
                passwords  : [],
                folders    : [],
                tags       : [],
                loading    : true,
                style      : '',
                contextMenu: false,
                sorting    : {
                    field    : SettingsService.get('client.ui.sorting.field', 'label'),
                    ascending: SettingsService.get('client.ui.sorting.ascending', true)
                },
                ui         : {
                    showTags: SettingsService.get('client.ui.list.tags.show', false) && window.innerWidth > 360
                },
                search     : SearchManager.status,
                timeout    : null
            };
        },

        created() {
            this.triggerViewUpdate();
            Events.on('data.changed', this.triggerViewUpdate);
            SearchManager.clearDatabase();
            subscribe('passwords:contextmenu:opened', this.positionContextMenu);
            subscribe('passwords:contextmenu:closed', this.closeContextMenu);
            subscribe('passwords:batch-action:completed', this.triggerViewUpdate);
        },

        beforeDestroy() {
            Events.off('data.changed', this.triggerViewUpdate);
            SearchManager.clearDatabase();
            BatchActionManager.clearSelectedItems();
            unsubscribe('passwords:contextmenu:opened', this.positionContextMenu);
            unsubscribe('passwords:contextmenu:closed', this.closeContextMenu);
            unsubscribe('passwords:batch-action:completed', this.triggerViewUpdate);
        },

        computed: {
            getContentClass() {
                let classes = {
                        'loading': this.loading
                    },
                    section = `section-${this.$route.name.toLowerCase()}`;
                classes[section] = true;

                return classes;
            },
            isNotEmpty() {
                return !this.loading && !this.isEmpty;
            },
            isEmpty() {
                if(this.loading) return false;
                if(this.search.active && this.search.total === 0) return true;

                return !this.passwords.length && !this.folders.length && !this.tags.length;
            },
            getEmptyText() {
                if(this.search.active) {
                    return LocalisationService.translate(
                        'We could not find anything for "{query}"',
                        {query: this.search.query}
                    );
                }

                return undefined;
            },
            getBreadcrumb() {
                return {};
            },
            isDraggable() {
                return false;
            }
        },

        methods: {
            triggerViewUpdate() {
                if(this.timeout) {
                    clearTimeout(this.timeout);
                }

                if(BatchActionManager.isProcessingItems) {
                    this.timeout = null;
                    return;
                }

                this.timeout = setTimeout(
                    () => { this.refreshView(); },
                    100
                );
            },
            updateSorting($event) {
                this.sorting = $event;
                SettingsService.set('client.ui.sorting.field', $event.field);
                SettingsService.set('client.ui.sorting.ascending', $event.ascending);

                if(this.passwords) {
                    this.passwords =
                        UtilityService.sortApiObjectArray(
                            this.passwords,
                            this.getPasswordsSortingField(),
                            this.sorting.ascending
                        );
                }
                if(this.folders) {
                    this.folders =
                        UtilityService.sortApiObjectArray(this.folders, this.sorting.field, this.sorting.ascending);
                }
                if(this.tags) {
                    this.tags =
                        UtilityService.sortApiObjectArray(this.tags, this.sorting.field, this.sorting.ascending);
                }
            },
            updatePasswordList(passwords) {
                this.loading = false;
                this.passwords =
                    UtilityService.sortApiObjectArray(passwords, this.getPasswordsSortingField(), this.sorting.ascending);
            },
            updateFolderList(folders) {
                this.loading = false;
                this.folders = UtilityService.sortApiObjectArray(folders, this.sorting.field, this.sorting.ascending);
            },
            updateTagList(tags) {
                this.loading = false;
                this.tags = UtilityService.sortApiObjectArray(tags, this.sorting.field, this.sorting.ascending);
            },
            getPasswordsSortingField() {
                let sortingField = this.sorting.field === 'label' ? SettingsService.get(
                    'client.ui.password.field.sorting'):this.sorting.field;
                if(sortingField === 'byTitle') sortingField = SettingsService.get('client.ui.password.field.title');
                return sortingField;
            },
            positionContextMenu(event) {
                this.contextMenu = true;
                this.style = `--mouse-pos-x:${Math.max(300, event.pos.x - 125)}px;--mouse-pos-y:${Math.max(0, event.pos.y - 30)}px`;
            },
            closeContextMenu() {
                this.contextMenu = false;
                this.style = '';
            }
        },
        watch  : {
            passwords(passwords) {
                let db = {passwords, folders: this.folders, tags: this.tags};
                SearchManager.setDatabase(db);
                BatchActionManager.setVisibleItems(this.folders, this.tags, passwords);
            },
            tags(tags) {
                let db = {passwords: this.passwords, folders: this.folders, tags};
                SearchManager.setDatabase(db);
                BatchActionManager.setVisibleItems(this.folders, tags, this.passwords);
            },
            folders(folders) {
                let db = {passwords: this.passwords, folders, tags: this.tags};
                SearchManager.setDatabase(db);
                BatchActionManager.setVisibleItems(folders, this.tags, this.passwords);
            }
        }
    };
</script>

<style lang="scss">
#app-content {
    height                     : calc(100vh - var(--header-height));
    overflow-y                 : initial;
    overflow-x                 : initial;
    border-top-right-radius    : var(--body-container-radius);
    border-bottom-right-radius : var(--body-container-radius);

    .item-list.context-menu {
        .v-popper__popper {
            transform : translate3d(var(--mouse-pos-x), var(--mouse-pos-y), 0px) !important;
        }
    }
}
</style>
