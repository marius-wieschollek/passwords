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
    <app-navigation-item ref="navigation-item" :name="t('Favorites')" :to="{ name: 'Favorites'}" :allowCollapse="true" :open="open" :loading="loading" v-on:update:open="loadFavorites">
        <star-icon :size="20" slot="icon"/>
        <template>
            <app-navigation-item
                    v-for="folder in folders"
                    :key="folder.id"
                    :name="folder.label"
                    :to="{ name: 'Folders', params: {folder: folder.id}}"
                    :exact="true"
                    :data-folder-id="folder.id"
                    data-drop-type="folder"
            >
                <folder-icon :size="20" :fill-color="folderIconColor(folder.id)" slot="icon"/>
            </app-navigation-item>
            <app-navigation-item
                    v-for="tag in tags"
                    :key="tag.id"
                    :name="tag.label"
                    :to="{ name: 'Tags', params: {tag: tag.id}}"
                    :exact="true"
                    :data-tag-id="tag.id"
                    data-drop-type="tag"
            >
                <tag-icon :size="20" slot="icon" :fill-color="tag.color"/>
            </app-navigation-item>
            <nc-loading-icon v-if="foldersLoaded === 0 || tagsLoaded === 0"/>
        </template>
    </app-navigation-item>
</template>
<script>
    import AppNavigationItem from "@vc/Navigation/AppNavigationItem";
    import NcLoadingIcon from "@nc/NcLoadingIcon.js";
    import FolderIcon from "@icon/Folder";
    import StarIcon from "@icon/Star";
    import TagIcon from "@icon/Tag";
    import Events from "@js/Classes/Events";
    import API from "@js/Helper/api";
    import Utility from "@js/Classes/Utility";
    import {subscribe} from "@nextcloud/event-bus";

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
                if(this.$refs['navigation-item'].opened) {
                    this.refreshTags();
                } else if(this.tags.length !== 0) {
                    this.tags = [];
                    this.tagsLoaded = false;
                }
            });
            Events.on('folder.changed', () => {
                if(this.$refs['navigation-item'].opened) {
                    this.refreshFolders();
                } else if(this.folders.length !== 0) {
                    this.folders = [];
                    this.foldersLoaded = false;
                }
            });
            subscribe('passwords:encryption:installed', () => {
                if(this.$refs['navigation-item'].opened) {
                    this.refreshTags();
                    this.refreshFolders();
                } else if(this.folders.length !== 0 || this.tags.length !== 0) {
                    this.folders = [];
                    this.foldersLoaded = false;
                    this.tags = [];
                    this.tagsLoaded = false;
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
            },
            folderIconColor(id) {
                if(this.$route.params?.folder === id) {
                    return 'var(--color-primary-element-text)';
                }

                return 'var(--color-primary)';
            }
        }
    };
</script>
