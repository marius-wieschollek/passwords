<template>
    <div class="row folder"
         :data-folder-id="folder.id"
         data-drop-type="folder"
         @click="openAction($event)"
         @dragstart="dragStartAction($event)">
        <i class="fa fa-star favourite" :class="{ active: folder.favourite }" @click="favouriteAction($event)"></i>
        <div class="favicon" :style="{'background-image': 'url(' + folder.icon + ')'}">&nbsp;</div>
        <span class="title">{{ folder.label }}</span>
        <slot name="middle"/>
        <div class="more" @click="toggleMenu($event)">
            <i class="fa fa-ellipsis-h"></i>
            <div class="folderActionsMenu popovermenu bubble menu" :class="{ open: showMenu }">
                <slot name="menu">
                    <ul>
                        <slot name="menu-top"/>
                        <!-- <translate tag="li" @click="detailsAction($event)" icon="info" say="Details"/> -->
                        <translate tag="li" @click="renameAction()" icon="pencil" say="Rename"/>
                        <translate tag="li" @click="deleteAction()" icon="trash" say="Delete"/>
                        <slot name="menu-bottom"/>
                    </ul>
                </slot>
            </div>
        </div>
        <div class="date">{{ getDate }}</div>
    </div>
</template>

<script>
    import $ from "jquery";
    import Translate from '@vc/Translate';
    import Utility from "@js/Classes/Utility";
    import DragManager from '@js/Manager/DragManager';
    import FolderManager from '@js/Manager/FolderManager';

    export default {
        components: {
            Translate
        },

        props: {
            folder: {
                type: Object
            }
        },

        data() {
            return {
                showMenu: false,
            }
        },

        computed: {
            getDate() {
                return Utility.formatDate(this.folder.edited);
            }
        },


        methods: {
            favouriteAction($event) {
                $event.stopPropagation();
                this.folder.favourite = !this.folder.favourite;
                FolderManager.updateFolder(this.folder)
                    .catch(() => { this.folder.favourite = !this.folder.favourite; });
            },
            toggleMenu($event) {
                this.showMenu = !this.showMenu;
                this.showMenu ? $(document).click(this.menuEvent):$(document).off('click', this.menuEvent);
            },
            menuEvent($e) {
                if($($e.target).closest('[data-folder-id=' + this.folder.id + '] .more').length !== 0) return;
                this.showMenu = false;
                $(document).off('click', this.menuEvent);
            },
            openAction($event) {
                if($($event.target).closest('.more').length !== 0) return;
                this.$router.push({name: 'Folders', params: {folder: this.folder.id}});
            },
            detailsAction($event, section = null) {
                this.$parent.detail = {
                    type   : 'folder',
                    element: this.folder
                }
            },
            deleteAction(skipConfirm = false) {
                FolderManager.deleteFolder(this.folder);
            },
            renameAction() {
                FolderManager.renameFolder(this.folder)
                    .then((f) => {this.folder = f;});
            },
            dragStartAction($e) {
                DragManager.start($e, this.folder.label, this.folder.icon, ['folder'])
                    .then((data) => {
                        FolderManager.moveFolder(this.folder, data.folderId)
                            .then((f) => {this.folder = f;});
                    });
            }
        }
    }
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