<template>
    <div class="row folder" v-if="enabled" :data-folder-id="folder.id" @dragstart="dragStartAction($event)" data-drop-type="folder" @click="openAction()">
        <i class="fa fa-star favourite" v-bind:class="{ active: folder.favourite }" @click="favouriteAction($event)"></i>
        <div class="favicon" v-bind:style="faviconStyle">&nbsp;</div>
        <span class="title">{{ folder.label }}</span>
        <div class="date">{{ folder.updated.toLocaleDateString() }}</div>
        <div class="more" @click="toggleMenu($event)">
            <i class="fa fa-ellipsis-h"></i>
            <div class="folderActionsMenu popovermenu bubble menu" :class="{ open: showMenu }">
                <slot name="menu">
                <ul>
                    <slot name="option-top"></slot>
                    <translate tag="li" @click="detailsAction($event)" icon="info">Details</translate>
                    <translate tag="li" @click="renameAction()" icon="pencil">Rename</translate>
                    <translate tag="li" @click="deleteAction()" icon="trash">Delete</translate>
                    <slot name="option-bottom"></slot>
                </ul>
                </slot>
            </div>
        </div>
        <slot name="buttons"></slot>
    </div>
</template>

<script>
    import Translate from '@vc/Translate.vue';
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
                enabled: true,
                showMenu: false,
            }
        },

        computed: {
            faviconStyle() {
                return {
                    backgroundImage: 'url(' + this.folder.icon + ')'
                }
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
                $event.stopPropagation();
                this.showMenu = !this.showMenu;
            },
            openAction() {
                this.$router.push({name: 'Folders', params: {folder: this.folder.id}});
            },
            detailsAction($event, section = null) {
                this.$parent.detail = {
                    type   : 'folder',
                    element: this.folder
                }
            },
            deleteAction(skipConfirm = false) {
                FolderManager.deleteFolder(this.folder)
                    .then(() => {this.enabled = false;});
            },
            renameAction() {
                FolderManager.renameFolder(this.folder)
                    .then((f) => {this.folder = f;});
            },
            dragStartAction($e) {
                DragManager.start($e, this.folder.label, this.folder.icon, ['folder'])
                    .then((data) => {
                        this.enabled = false;
                        FolderManager.moveFolder(this.folder, data.folderId)
                            .then((f) => {this.folder = f;})
                            .catch(() => {this.enabled = true;});
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