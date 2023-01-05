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
    <nc-modal
            class="pw-folder-picker-dialog"
            ref="window"
            :inlineActions="0"
            v-on:close="close"
            :title="t('Select a folder')">
        <template #default>
            <div class="pw-folder-picker" v-if="currentFolder !== null">
                <picker-breadcrumb :current="currentFolder" :folders="folderList" v-on:navigate="openFolder"/>
                <picker-folder-list :current="currentFolder" :folders="currentFolders" :ignored-folders="ignoredFolders" v-on:navigate="openFolder"/>
                <div class="buttons">
                    <nc-button @click="select" type="primary">
                        {{ t('Select "{folder}"', {folder: currentFolder.label}) }}
                    </nc-button>
                </div>
            </div>
            <nc-loading-icon class="pw-folder-picker-loading" :title="t('FolderPickerLoading')" :size="40" v-else/>
        </template>
    </nc-modal>
</template>

<script>
    import Translate from '@vc/Translate';
    import API from '@js/Helper/api';
    import Utility from '@js/Classes/Utility';
    import PickerBreadcrumb from '@vue/Dialog/FolderPicker/PickerBreadcrumb';
    import PickerFolderList from '@vue/Dialog/FolderPicker/PickerFolderList';
    import Localisation from '@js/Classes/Localisation';
    import NcModal from '@nc/NcModal';
    import NcButton from '@nc/NcButton';
    import NcLoadingIcon from '@nc/NcLoadingIcon'

    export default {
        components: {PickerFolderList, PickerBreadcrumb, Translate, NcModal, NcButton, NcLoadingIcon},
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
                    if(item.parent === this.currentFolder.id && item.id !== item.parent) {
                        currentFolders.push(item);
                    }
                }

                return Utility.sortApiObjectArray(currentFolders, 'label');
            }
        },
        methods : {
            close() {
                this.reject();
                this.$destroy();
                this.$el.parentNode.removeChild(this.$el);
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
                this.folderList.push({
                                         id    : '00000000-0000-0000-0000-000000000000',
                                         parent: '00000000-0000-0000-0000-000000000000',
                                         label : Localisation.translate('Home')
                                     });
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
        overflow-x : hidden;;

        .buttons {
            position : absolute;
            bottom   : .5rem;
            right    : .5rem
        }
    }

    .pw-folder-picker-loading {
        height: 360px;
    }

    .button-vue.modal-container__close {
        z-index : 1;
    }
}
</style>