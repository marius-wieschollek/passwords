<template>
    <div id="app-content" :class="getContentClass">
        <div class="app-content-left">
            <breadcrumb :newTag="!currentTag" :newPassword="currentTag !== null" :tag="currentTag" :items="breadcrumb"/>
            <div class="item-list">
                <header-line :field="sorting.field" :ascending="sorting.ascending" v-on:updateSorting="updateSorting($event)" v-if="showHeaderAndFooter"/>
                <tag-line :tag="tag" v-for="tag in tags" :key="tag.id"/>
                <password-line :password="password" v-for="password in passwords" :key="password.id"/>
                <footer-line :passwords="passwords" :tags="tags" v-if="showHeaderAndFooter"/>
                <empty v-if="isEmpty"/>
            </div>
        </div>
        <div class="app-content-right">
            <password-details v-if="showPasswordDetails" :password="detail.element"/>
        </div>
    </div>
</template>

<script>
    import API from '@js/Helper/api';
    import TagLine from '@vue/Line/Tag';
    import Breadcrumb from '@vc/Breadcrumbs';
    import Utility from "@js/Classes/Utility";
    import HeaderLine from "@vue/Line/Header";
    import FooterLine from "@vue/Line/Footer";
    import Empty from "@vue/Components/Empty";
    import PasswordLine from '@vue/Line/Password';
    import BaseSection from '@vue/Section/BaseSection';
    import PasswordDetails from '@vue/Details/Password';

    export default {
        extends: BaseSection,

        components: {
            Empty,
            TagLine,
            Breadcrumb,
            HeaderLine,
            FooterLine,
            PasswordLine,
            PasswordDetails
        },

        data() {
            return {
                currentTag  : null,
                defaultTitle: Utility.translate('Tags'),
                defaultPath : '/tags/',
                tags        : []
            };
        },

        methods: {
            refreshView: function() {
                this.breadcrumb = [];

                this.detail.type = 'none';
                if(this.$route.params.tag !== undefined) {
                    let tag = this.$route.params.tag;
                    this.currentTag = tag;
                    this.tags = [];
                    if(!this.passwords.length) this.loading = true;
                    API.showTag(tag, 'model+passwords').then(this.updatePasswordList);
                } else {
                    this.passwords = [];
                    this.currentTag = null;
                    if(!this.tags.length) this.loading = true;
                    API.listTags().then(this.updateTagList);
                }
            },

            updatePasswordList: function(tag) {
                this.loading = false;

                if(tag.trashed) {
                    this.defaultTitle = Utility.translate('Trash');
                    this.defaultPath = '/trash';
                }

                this.passwords = Utility.sortApiObjectArray(tag.passwords, this.getPasswordsSortingField(), this.sorting.ascending);
                this.breadcrumb = [
                    {path: this.defaultPath, label: this.defaultTitle},
                    {path: this.$route.path, label: tag.label}
                ];
            }
        },
        watch  : {
            $route: function() {
                this.refreshView();
            }
        }
    };
</script>