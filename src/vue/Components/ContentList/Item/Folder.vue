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
         @contextmenu.stop.prevent="contextMenu = $event"
         @dragstart="dragStartAction($event)"
         :data-pw-id="folder.id"
         :data-pw-label="folder.label"
         data-pw-item="folder"
         data-pw-drop-type="folder">
        <folder-item-batch-toggle :item="folder"/>
        <folder-item-favicon :title="folder.label" v-model="isSelected"/>
        <div class="title" :title="folder.label">
            <button :aria-label="t('FolderListItemAriaLabel', {label: folder.label})">{{ folder.label }}</button>
        </div>
        <slot name="middle"/>
        <slot name="actions">
            <folder-item-action-menu :actions="actions" :folder="folder" v-model="contextMenu">
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
    import ContentItemMenuLoadingIcon from "@vc/ContentList/Item/ContentItem/ContentItemMenuLoadingIcon.vue";

    export default {
        components: {
            NcDateTime,
            FolderItemFavicon,
            FolderItemBatchToggle,
            'folder-item-action-menu': () => ({
                component: import(/* webpackChunkName: "FolderItemActionMenu" */ '@vc/ContentList/Item/FolderItem/FolderItemActionMenu.vue'),
                loading  : ContentItemMenuLoadingIcon,
                delay    : 0
            })
        },

        props: {
            folder: {
                type: Object
            }
        },

        data() {
            return {
                isSelected : false,
                contextMenu: null,
                actions    : new FolderActions(this.folder)
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
            dragStartAction($e) {
                DragManager
                    .start($e, this.folder)
                    .then(async (data) => {
                        if(data.dropType === 'folder') {
                            this.actions.move(data.pwId);
                        } else if(data.dropType === 'favorite') {
                            this.folder = await this.actions.favorite(true);
                        } else if(data.dropType === 'trash') {
                            this.actions.delete(this.folder).catch(LoggingService.catch);
                        }
                    });
            }
        },
    };
</script>