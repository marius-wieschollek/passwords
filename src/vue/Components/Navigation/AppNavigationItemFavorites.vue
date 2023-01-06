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
    <app-navigation-item ref="navigation-item" :title="t('Favorites')" :to="{ name: 'Favorites'}" :allowCollapse="true" :open="open" :loading="loading" v-on:update:open="loadFavorites">
        <star-icon slot="icon"/>
        <template>
            <app-navigation-item
                    v-for="folder in folders"
                    :key="folder.id"
                    :title="folder.label"
                    :to="{ name: 'Folders', params: {folder: folder.id}}"
                    :exact="true"
                    :data-folder-id="folder.id"
                    data-drop-type="folder"
            >
                <folder-icon fill-color="var(--color-primary-default)" slot="icon"/>
            </app-navigation-item>
            <app-navigation-item
                    v-for="tag in tags"
                    :key="tag.id"
                    :title="tag.label"
                    :to="{ name: 'Tags', params: {tag: tag.id}}"
                    :exact="true"
                    :data-tag-id="tag.id"
                    data-drop-type="tag"
            >
                <tag-icon slot="icon" :fill-color="tag.color"/>
            </app-navigation-item>
            <nc-loading-icon v-if="foldersLoaded === 0 || tagsLoaded === 0"/>
        </template>
    </app-navigation-item>
</template>
<script>
    import AppNavigationItem from "@vc/Navigation/AppNavigationItem";
    import NcLoadingIcon from "@nc/NcLoadingIcon";
    import FolderIcon from "@icon/Folder";
    import StarIcon from "@icon/Star";
    import TagIcon from "@icon/Tag";
    import Events from "@js/Classes/Events";
    import API from "@js/Helper/api";
    import Utility from "@js/Classes/Utility";

    export default {
        name      : 'app-navigation-item-favorites',
        components: {AppNavigationItem, NcLoadingIcon, TagIcon, FolderIcon, StarIcon},
        data() {
            return {
                open         : false,
                loading      : false,
                tags         : [],
                folders      : [],
                foldersLoaded: false,
                tagsLoaded   : false
            };
        },
        mounted() {
            this.open = this.$route.name === 'Tags';
            if(this.open) {
                this.loadFavorites();
            }
            Events.on('tag.changed', () => {
                if(this.$refs['navigation-item'].opened || this.tags.length !== 0) {
                    this.refreshTags();
                }
            });
            Events.on('folder.changed', () => {
                if(this.$refs['navigation-item'].opened || this.folders.length !== 0) {
                    this.refreshFolders();
                }
            });
        },
        methods: {
            loadFavorites() {
                if(this.tags.length === 0) {
                    this.refreshTags();
                }
                if(this.folders.length === 0) {
                    this.refreshFolders();
                }
            },
            refreshTags() {
                this.loading = true;
                API.findTags({favorite: true})
                   .then((d) => {
                       this.tags = Utility.sortApiObjectArray(d, 'label');
                       this.loading = false;
                   });
            },
            refreshFolders() {
                this.loading = true;
                API.findFolders({favorite: true})
                   .then((d) => {
                       this.folders = Utility.sortApiObjectArray(d, 'label');
                       this.loading = false;
                   });
            }
        }
    };
</script>
