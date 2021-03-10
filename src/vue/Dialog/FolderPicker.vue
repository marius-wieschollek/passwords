<!--
  - @copyright 2021 Passwords App
  -
  - @author Marius David Wieschollek
  - @license AGPL-3.0
  -
  - This file is part of the Passwords App
  - created by Marius David Wieschollek.
  -->

<template>
    <dialog-window class="pw-folder-picker-dialog" ref="window" v-on:close="close" title="Choose folder">
        <div class="pw-folder-picker" slot="content" v-if="currentFolder !== null">
            <picker-breadcrumb :current="currentFolder" :folders="folderList" v-on:navigate="openFolder"/>
            <picker-folder-list :folders="currentFolders" v-on:navigate="openFolder"/>
        </div>
        <div class="pw-folder-picker loading" slot="content" v-else/>
        <div class="buttons" slot="controls" v-if="currentFolder !== null">
            <translate class="button primary" @click="select" say="Select &quot;{folder}&quot;" :variables="{folder: currentFolder.label}"/>
        </div>
    </dialog-window>
</template>

<script>
    import DialogWindow from "@vue/Dialog/DialogWindow";
    import Translate from "@vc/Translate";
    import API from '@js/Helper/api';
    import Utility from "@js/Classes/Utility";
    import PickerBreadcrumb from "@vue/Dialog/FolderPicker/PickerBreadcrumb";
    import PickerFolderList from "@vue/Dialog/FolderPicker/PickerFolderList";

    export default {
        components: {PickerFolderList, PickerBreadcrumb, Translate, DialogWindow},
        props     : {
            folder        : {
                type   : String,
                default: '00000000-0000-0000-0000-000000000000'
            },
            ignoredFolders: {
                type   : Array,
                default: []
            },
            resolve       : Function,
            reject        : Function
        },
        data() {
            return {
                folderList   : [],
                currentFolder: null
            };
        },
        mounted() {
            this.loadFolders();
        },
        computed: {
            currentFolders() {
                let currentFolders = [];

                for(let item of this.folderList) {
                    if(item.parent === this.currentFolder.id && item.id !== item.parent && this.ignoredFolders.indexOf(item.id) === -1) {
                        currentFolders.push(item);
                    }
                }

                return Utility.sortApiObjectArray(currentFolders, 'label');
            }
        },
        methods : {
            close() {
                this.reject();
            },
            select() {
                this.resolve(this.currentFolder);
                this.$refs.window.closeWindow();
            },
            async loadFolders() {
                let current = await API.showFolder(this.folder, 'model+folders');
                this.currentFolder = current;
                this.folderList = Utility.objectToArray(current.folders);
                this.folderList = Utility.objectToArray(await API.listFolders());
            },
            openFolder(folder) {
                this.currentFolder = folder;
            }
        }
    };
</script>

<style lang="scss">
.pw-folder-picker-dialog {
    .pw-folder-picker {
        width      : calc(100vw - 1rem);
        max-width  : 525px;
        min-height : 360px;
        max-height : 360px;
    }

    .buttons {
        display         : flex;
        width           : 100%;
        justify-content : flex-end;
    }
}
</style>