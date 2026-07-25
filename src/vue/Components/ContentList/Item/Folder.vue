<!--
  - @copyright 2026 Passwords App
  -
  - @author Marius David Wieschollek
  - @license AGPL-3.0
  -
  - This file is part of the Passwords App
  - created by Marius David Wieschollek.
  -->

<template>
    <div :class="className"
         @click="openAction($event)"
         @contextmenu="openContextMenu"
         @dragstart="dragStartAction($event)"
         :data-folder-id="folder.id"
         :data-folder-title="folder.label"
         data-drop-type="folder">
        <folder-item-batch-toggle :item="folder"/>
        <folder-item-favicon :title="folder.label" v-model="isSelected"/>
        <div class="title" :title="folder.label">
            <button :aria-label="t('FolderListItemAriaLabel', {label: folder.label})">{{ folder.label }}</button>
        </div>
        <slot name="middle"/>
        <slot name="actions">
            <folder-item-action-menu
                    :actions="actions"
                    :folder="folder"
                    :opened-menu.sync="openedMenu"
                    @closed="openedMenu = false"
            >
                <template v-if="hasCustomAction" #custom-action>
                    <slot name="custom-action"/>
                </template>
                <template #restore-action>
                    <slot name="restore-action"/>
                </template>
            </folder-item-action-menu>
        </slot>
        <nc-date-time class="date" :timestamp="folder.edited"/>
    </div>
</template>

<script>
    import DragManager from '@js/Manager/DragManager';
    import SearchManager from "@js/Manager/SearchManager";
    import FolderItemBatchToggle from "@vc/ContentList/Item/FolderItem/FolderItemBatchToggle.vue";
    import FolderItemFavicon from "@vc/ContentList/Item/FolderItem/FolderItemFavicon.vue";
    import FolderActions from "@js/Actions/Folder/FolderActions";
    import LoggingService from "@js/Services/LoggingService";
    import NcDateTime from "@nextcloud/vue/components/NcDateTime";
    import {emit} from "@nextcloud/event-bus";
    import PasswordItemActionMenu from "@vc/ContentList/Item/PasswordItem/PasswordItemActionMenu.vue";
    import PasswordItemSecurityIcon from "@vc/ContentList/Item/PasswordItem/PasswordItemSecurityIcon.vue";

    export default {
        components: {
            PasswordItemSecurityIcon,
            PasswordItemActionMenu,
            NcDateTime,
            FolderItemFavicon,
            FolderItemBatchToggle,
            'folder-item-action-menu': () => import(/* webpackChunkName: "FolderItemActionMenu" */ '@vc/ContentList/Item/FolderItem/FolderItemActionMenu.vue')
        },

        props: {
            folder: {
                type: Object
            }
        },

        data() {
            return {
                openedMenu: false,
                isSelected: false,
                actions   : new FolderActions(this.folder)
            };
        },

        computed: {
            className() {
                let classNames = 'row folder';

                if(this.isSelected) classNames += ' selected';
                if(SearchManager.status.active) {
                    classNames += SearchManager.status.ids.indexOf(this.folder.id) !== -1 ? ' search-visible':' search-hidden';
                }

                return classNames;
            },
            hasCustomAction() {
                return this.$slots.hasOwnProperty('custom-action');
            }
        },

        methods: {
            openAction($event) {
                if($event.target.closest('.checkbox-radio-switch') !== null) return;
                this.$router.push({name: 'Folders', params: {folder: this.folder.id}});
            },
            openContextMenu(event) {
                if(this.openedMenu) {
                    return;
                }

                this.openedMenu = true;
                emit('passwords:contextmenu:opened', {item: this.folder, pos: {x: event.clientX, y: event.clientY}});

                event.preventDefault();
                event.stopPropagation();
            },
            dragStartAction($e) {
                DragManager
                    .start($e, this.folder)
                    .then((data) => {
                        if(data.dropType === 'folder') {
                            this.actions.move(data.folderId);
                        } else if(data.dropType === 'trash') {
                            this.actions.delete(this.folder).catch(LoggingService.catch);
                        }
                    });
            }
        },

        watch: {
            openedMenu(value) {
                if(!value) {
                    emit('passwords:contextmenu:closed', {item: this.folder});
                }
            }
        }
    };
</script>