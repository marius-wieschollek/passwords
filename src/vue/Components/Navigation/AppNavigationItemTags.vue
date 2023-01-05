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
    <app-navigation-item :title="t('Tags')" :to="{ name: 'Tags'}" :allowCollapse="true" :open="open" :loading="loading" v-on:update:open="loadTags">
        <tag-icon slot="icon"/>
        <template>
            <app-navigation-item v-for="tag in tags" :title="tag.label" :to="{ name: 'Tags', params: {tag: tag.id}}" :exact="true">
                <tag-icon slot="icon" :fill-color="tag.color"/>
            </app-navigation-item>
            <nc-loading-icon v-if="tags.length === 0"/>
        </template>
    </app-navigation-item>
</template>
<script>
    import API from '@js/Helper/api';
    import AppNavigationItem from "@vc/Navigation/AppNavigationItem";
    import NcLoadingIcon from "@nc/NcLoadingIcon";
    import TagIcon from "@icon/Tag";
    import Events from "@js/Classes/Events";
    import Utility from '@js/Classes/Utility';

    export default {
        components: {AppNavigationItem, TagIcon, NcLoadingIcon},
        data() {
            return {
                open   : false,
                loading: false,
                tags   : []
            };
        },
        mounted() {
            this.open = this.$route.name === 'Tags';
            if(this.open) {
                this.loadTags();
            }
            Events.on('tag.changed', () => {
                if(this.tags.length !== 0) {
                    this.refreshTags();
                }
            });
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
                       this.tags = Utility.sortApiObjectArray(d, 'label');
                       this.loading = false;
                   });
            }
        }
    };
</script>
