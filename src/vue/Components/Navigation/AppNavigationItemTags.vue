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
    <app-navigation-item ref="navigation-item" :name="t('Tags')" :to="{ name: 'Tags'}" :allowCollapse="true" :exact="requiresExact" :open="open" :loading="loading" v-on:update:open="loadTags">
        <tag-icon :size="20" slot="icon"/>
        <template>
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
            <nc-loading-icon v-if="!hasLoaded"/>
        </template>
    </app-navigation-item>
</template>
<script>
    import API from '@js/Helper/api';
    import AppNavigationItem from "@vc/Navigation/AppNavigationItem";
    import NcLoadingIcon from "@nc/NcLoadingIcon.js";
    import TagIcon from "@icon/Tag";
    import Events from "@js/Classes/Events";
    import {subscribe} from "@nextcloud/event-bus";
    import UtilityService from "@js/Services/UtilityService";

    export default {
        components: {AppNavigationItem, TagIcon, NcLoadingIcon},
        data() {
            return {
                open     : false,
                loading  : false,
                tags     : [],
                hasLoaded: false
            };
        },
        mounted() {
            this.open = this.$route.name === 'Tags';
            if(this.open) {
                this.loadTags();
            }
            let refreshEvent = () => {
                if(this.$refs['navigation-item'].opened) {
                    this.refreshTags();
                } else if(this.tags.length !== 0) {
                    this.tags = [];
                    this.tagsLoaded = false;
                }
            };

            Events.on('tag.changed', refreshEvent);
            subscribe('passwords:encryption:installed', refreshEvent);
        },
        computed: {
            requiresExact() {
                return this.tags.find((tags) => {return tags.id === this.$route.params?.tags;}) !== undefined
            }
        },
        methods: {
            loadTags() {
                if(this.tags.length !== 0) {
                    return;
                }
                this.refreshTags();
            },
            refreshTags() {
                this.loading = true;
                API.listTags()
                   .then((d) => {
                       this.tags = UtilityService.sortApiObjectArray(d, 'label');
                       this.hasLoaded = true;
                       this.loading = false;
                   });
            }
        }
    };
</script>
