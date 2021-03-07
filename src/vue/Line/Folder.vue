<template>
    <div :class="className"
         @click="openAction($event)"
         @dragstart="dragStartAction($event)"
         :data-folder-id="folder.id"
         :data-folder-title="folder.label"
         data-drop-type="folder">
        <i class="fa fa-star favorite" :class="{ active: folder.favorite }" @click="favoriteAction($event)"></i>
        <div class="favicon" :style="{'background-image': 'url(' + folder.icon + ')'}" :title="folder.label">&nbsp;</div>
        <div class="title" :title="folder.label"><span>{{ folder.label }}</span></div>
        <slot name="middle"/>
        <div class="more" @click="toggleMenu($event)">
            <i class="fa fa-ellipsis-h"></i>
            <div class="folderActionsMenu popovermenu bubble menu" :class="{ open: showMenu }">
                <slot name="menu">
                    <ul>
                        <slot name="menu-top"/>
                        <!-- <translate tag="li" @click="detailsAction($event)" icon="info" say="Details"/> -->
                        <translate tag="li" @click="renameAction()" icon="pencil" say="Rename"/>
                        <translate tag="li" @click="moveAction" icon="external-link" say="Move"/>
                        <translate tag="li" @click="deleteAction()" icon="trash" say="Delete"/>
                        <slot name="menu-bottom"/>
                    </ul>
                </slot>
            </div>
        </div>
        <div class="date" :title="dateTitle">{{ getDate }}</div>
    </div>
</template>

<script>
    import $ from "jquery";
    import Translate from '@vc/Translate';
    import DragManager from '@js/Manager/DragManager';
    import Localisation from "@js/Classes/Localisation";
    import FolderManager from '@js/Manager/FolderManager';
    import SearchManager from "@js/Manager/SearchManager";

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
                showMenu: false
            };
        },

        computed: {
            getDate() {
                return Localisation.formatDate(this.folder.edited);
            },
            dateTitle() {
                return Localisation.translate('Last modified on {date}', {date:Localisation.formatDate(this.folder.edited, 'long')});
            },
            className() {
                let classNames = 'row folder';

                if(SearchManager.status.active) {
                    if(SearchManager.status.ids.indexOf(this.folder.id) !== -1) {
                        classNames += ' search-visible';
                    } else {
                        classNames += ' search-hidden';
                    }
                }

                return classNames;
            }
        },


        methods: {
            favoriteAction($event) {
                $event.stopPropagation();
                this.folder.favorite = !this.folder.favorite;
                FolderManager.updateFolder(this.folder)
                             .catch(() => { this.folder.favorite = !this.folder.favorite; });
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
                };
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
                DragManager.start($e, this.folder.label, this.folder.icon, ['folder'])
                           .then((data) => {
                               FolderManager.moveFolder(this.folder, data.folderId)
                                            .then((f) => {this.folder = f;});
                           });
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