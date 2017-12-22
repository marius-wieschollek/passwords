<template>
    <div class="row folder" v-if="folder" :data-folder-id="folder.id" @dragstart="dragStartAction($event)" data-drop-type="folder" @click="openFolderAction()">
        <i class="fa fa-star favourite" v-bind:class="{ active: folder.favourite }" @click="favouriteAction($event)"></i>
        <div class="favicon">&nbsp;</div>
        <span class="title">{{ folder.label }}</span>
        <div class="date">{{ folder.updated.toLocaleDateString() }}</div>
        <div class="more" @click="toggleMenu($event)">
            <i class="fa fa-ellipsis-h"></i>
            <div class="folderActionsMenu popovermenu bubble menu">
                <slot name="menu">
                <ul>
                    <slot name="option-top"></slot>
                    <translate tag="li" @click="detailsAction($event);" icon="info">Details</translate>
                    <translate tag="li" @click="editAction()" icon="pencil">Rename</translate>
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
    import $ from "jquery";
    import API from '@js/Helper/api';
    import Utility from "@js/Classes/Utility";
    import PwMessages from '@js/Classes/Messages';
    import Translate from '@vc/Translate.vue';
    import DragManager from '@js/Manager/DragManager';

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
            }
        },

        computed: {
        },

        methods: {
            favouriteAction($event) {
                $event.stopPropagation();
                this.folder.favourite = !this.folder.favourite;
                API.updateFolder(this.folder);
            },
            toggleMenu($event) {
                $event.stopPropagation();
                $($event.target).parents('.row.folder').find('.folderActionsMenu').toggleClass('open');
            },
            openFolderAction() {
                this.$router.push({name: 'Folders', params: {folder: this.folder.id}});
            },
            detailsAction($event, section = null) {
                this.$parent.detail = {
                    type   : 'folder',
                    element: this.folder
                }
            },
            deleteAction(skipConfirm = false) {
                if(skipConfirm || !this.folder.trashed) {
                    API.deleteFolder(this.folder.id)
                        .then(() => {
                            this.folder = undefined;
                            PwMessages.notification('Folder was deleted');
                        }).catch(() => {
                        PwMessages.notification('Deleting folder failed');
                    });
                } else {
                    PwMessages.confirm('Do you want to delete the folder', 'Delete folder')
                        .then(() => { this.deleteAction(true); })
                }
            },
            dragStartAction($e) {
                DragManager.start($e, this.folder.label, 'http://localhost/index.php/apps/theming/img/core/filetypes/folder.svg?v=13', ['folder'])
                    .then((data) => {
                        this.folder.parent = data.folderId;
                        API.updateFolder(this.folder)
                            .then(() => {this.$parent.refreshView();});
                    });
            },
            editAction() {
                PwMessages
                    .prompt('Enter folder name', 'Rename folder', this.folder.label)
                    .then((title) => {
                        this.folder.label = title;

                        API.updateFolder(this.folder)
                            .then(() => {
                                PwMessages.notification('Folder renamed');
                            })
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
                    background-image:url(/index.php/apps/theming/img/core/filetypes/folder.svg?v=13);
                    background-size: 32px;
                }
            }
        }
    }

</style>