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
    <app-navigation-item ref="navigation-item" :title="t('Folders')" :to="{ name: 'Folders'}" :allowCollapse="true" :open="open" :loading="loading" v-on:update:open="loadFolders">
        <folder-icon slot="icon"/>
        <template>
            <app-navigation-item
                    v-for="folder in folders"
                    :title="folder.label"
                    :to="{ name: 'Folders', params: {folder: folder.id}}"
                    :exact="true"
                    :data-folder-id="folder.id"
                    data-drop-type="folder"
            >
                <folder-icon fill-color="var(--color-primary-default)" slot="icon"/>
            </app-navigation-item>
            <nc-loading-icon v-if="!hasLoaded"/>
        </template>
    </app-navigation-item>
</template>
<script>
    import API from '@js/Helper/api';
    import AppNavigationItem from "@vc/Navigation/AppNavigationItem";
    import NcLoadingIcon from "@nc/NcLoadingIcon";
    import FolderIcon from "@icon/Folder";
    import Utility from '@js/Classes/Utility';
    import Events from "@js/Classes/Events";

    export default {
        components: {AppNavigationItem, FolderIcon, NcLoadingIcon},
        data() {
            return {
                open     : false,
                loading  : false,
                folders  : [],
                baseId   : '00000000-0000-0000-0000-000000000000',
                hasLoaded: false
            };
        },
        mounted() {
            this.open = this.$route.name === 'Folders';
            if(this.open) {
                this.loadFolders();
            }
            Events.on('folder.changed', (event) => {
                let folder = event.object;
                if((this.$refs['navigation-item'].opened || this.folders.length !== 0) &&
                   (
                       folder.id === this.baseId ||
                       folder.parent === this.baseId ||
                       (folder.parent.hasOwnProperty('id') && folder.parent.id === this.baseId)
                   )
                ) {
                    this.refreshFolders();
                }
            });
        },
        methods: {
            loadFolders() {
                if(this.folders.length !== 0) {
                    return;
                }
                this.refreshFolders();
            },
            refreshFolders() {
                this.loading = true;

                API.showFolder(this.baseId, 'model+folders')
                   .then((d) => {
                       this.folders = Utility.sortApiObjectArray(Utility.objectToArray(d.folders), 'label');
                       this.loading = false;
                       this.hasLoaded = true;
                   });
            }
        }
    };
</script>
