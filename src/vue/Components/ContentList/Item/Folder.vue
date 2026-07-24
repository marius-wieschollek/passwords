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
         @dragstart="dragStartAction($event)"
         :data-folder-id="folder.id"
         :data-folder-title="folder.label"
         data-drop-type="folder">
        <nc-checkbox-radio-switch :checked.sync="isSelected" :loading="batchActionActive"/>
        <star-icon class="favorite" data-item-action="favorite" fill-color="var(--color-element-warning)" @click.prevent.stop="favoriteAction" v-if="folder.favorite"/>
        <star-outline-icon class="favorite" data-item-action="favorite" fill-color="var(--color-placeholder-dark)" @click.prevent.stop="favoriteAction" v-else/>
        <div class="favicon" :style="{'background-image': 'url(' + folder.icon + ')'}" :title="folder.label">&nbsp;</div>
        <div class="title" :title="folder.label"><span>{{ folder.label }}</span></div>
        <slot name="middle"/>
        <div class="more" @click="toggleMenu()" :aria-label="t('More')">
            <i class="fa fa-ellipsis-h">
                <a href="#" :aria-label="t('More')" :title="t('More')" @click.stop.prevent="toggleMenu()"></a>
            </i>
            <div class="folderActionsMenu popovermenu bubble menu" :class="{ open: showMenu }" @keydown.esc.stop.prevent="toggleMenu(false)">
                <slot name="menu">
                    <ul>
                        <slot name="menu-top"/>
                        <li>
                            <translate tag="a" href="#" data-item-action="edit" @click.prevent="renameAction()" icon="pencil" say="Rename"/>
                        </li>
                        <li>
                            <translate tag="a" href="#" data-item-action="move" @click.prevent="moveAction" icon="external-link" say="Move"/>
                        </li>
                        <li>
                            <translate tag="a" href="#" data-item-action="delete" @click.prevent="deleteAction()" icon="trash" say="Delete"/>
                        </li>
                        <slot name="menu-bottom"/>
                    </ul>
                </slot>
            </div>
        </div>
        <div class="date" :title="dateTitle">{{ getDate }}</div>
    </div>
</template>

<script>
    import Translate from '@vc/Translate';
    import DragManager from '@js/Manager/DragManager';
    import FolderManager from '@js/Manager/FolderManager';
    import SearchManager from "@js/Manager/SearchManager";
    import BatchActionManager from "@js/Manager/BatchActionManager";
    import StarIcon from "vue-material-design-icons/Star.vue";
    import StarOutlineIcon from "vue-material-design-icons/StarOutline.vue";
    import LocalisationService from "@js/Services/LocalisationService";
    import NcCheckboxRadioSwitch from "@nextcloud/vue/components/NcCheckboxRadioSwitch";

    export default {
        components: {
            Translate,
            StarIcon,
            StarOutlineIcon,
            NcCheckboxRadioSwitch
        },

        props: {
            folder: {
                type: Object
            }
        },

        data() {
            return {
                showMenu  : false,
                isSelected: false
            };
        },

        computed: {
            getDate() {
                return LocalisationService.formatDate(this.folder.edited);
            },
            dateTitle() {
                return LocalisationService.translate('Last modified on {date}', {date: LocalisationService.formatDateTime(this.folder.edited)});
            },
            className() {
                let classNames = 'row folder';

                if(this.isSelected) classNames += ' selected';
                if(SearchManager.status.active) {
                    classNames += SearchManager.status.ids.indexOf(this.folder.id) !== -1 ? ' search-visible':' search-hidden';
                }

                return classNames;
            },
            batchActionSelected() {
                return BatchActionManager.isFolderSelected(this.folder);
            },
            batchActionActive() {
                return BatchActionManager.isItemProcessed(this.folder);
            }
        },

        methods: {
            favoriteAction() {
                this.folder.favorite = !this.folder.favorite;
                FolderManager.updateFolder(this.folder)
                             .catch(() => { this.folder.favorite = !this.folder.favorite; });
            },
            toggleMenu(state = null) {
                if(state) {
                    this.showMenu = state === true;
                } else {
                    this.showMenu = !this.showMenu;
                }

                if(this.showMenu) {
                    document.addEventListener('click', this.menuEvent);
                } else {
                    document.removeEventListener('click', this.menuEvent);
                }
            },
            menuEvent($e) {
                if($e.target.closest('[data-folder-id="' + this.folder.id + '"] .more') !== null) return;
                this.showMenu = false;
                document.removeEventListener('click', this.menuEvent);
            },
            openAction($event) {
                if($event.target.closest('.more') !== null || $event.target.closest('.checkbox-radio-switch') !== null) return;
                this.$router.push({name: 'Folders', params: {folder: this.folder.id}});
            },
            deleteAction() {
                FolderManager.deleteFolder(this.folder);
            },
            moveAction() {
                FolderManager.moveFolder(this.folder);
            },
            renameAction() {
                FolderManager.renameFolder(this.folder)
                             .then((f) => {this.folder = f;});
            },
            dragStartAction($e) {
                DragManager
                    .start($e, this.folder)
                    .then((data) => {
                        if(data.dropType === 'folder') {
                            FolderManager
                                .moveFolder(this.folder, data.folderId)
                                .then((f) => {this.folder = f;});
                        } else if(data.dropType === 'trash') {
                            FolderManager.deleteFolder(this.folder);
                        }
                    });
            }
        },

        watch: {
            isSelected(value) {
                if(this.batchActionSelected !== value) {
                    BatchActionManager.toggleFolder(this.folder, value);
                }
            },
            batchActionSelected(value) {
                if(this.isSelected !== value) {
                    this.isSelected = value;
                }
            }
        }
    };
</script>

<style lang="scss">
#app-content {
    .item-list {
        .row.folder {
            .favicon {
                background-size : 32px;
            }
        }
    }
}

</style>