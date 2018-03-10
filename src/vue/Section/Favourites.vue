<template>
    <div id="app-content" :class="getContentClass">
        <div class="app-content-left">
            <breadcrumb :showAddNew="false"/>
            <div class="item-list">
                <header-line :field="sorting.field" :ascending="sorting.ascending" v-on:updateSorting="updateSorting($event)" v-if="showHeaderAndFooter"/>
                <folder-line :folder="folder" v-for="folder in folders" :key="folder.id"/>
                <tag-line :tag="tag" v-for="tag in tags" :key="tag.id"/>
                <password-line :password="password" v-for="password in passwords" :key="password.id"/>
                <footer-line :passwords="passwords" :folders="folders" :tags="tags" v-if="showHeaderAndFooter"/>
                <empty v-if="isEmpty" text="Your favorites will appear here"/>
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
    import Breadcrumb from '@vc/Breadcrumb';
    import FolderLine from '@vue/Line/Folder';
    import Empty from "@vue/Components/Empty";
    import HeaderLine from "@vue/Line/Header";
    import FooterLine from "@vue/Line/Footer";
    import PasswordLine from '@vue/Line/Password';
    import BaseSection from '@vue/Section/BaseSection';
    import PasswordDetails from '@vue/Details/Password';

    export default {
        extends: BaseSection,

        components: {
            Empty,
            TagLine,
            Breadcrumb,
            FolderLine,
            HeaderLine,
            FooterLine,
            PasswordLine,
            PasswordDetails
        },

        data() {
            return {
                folders: [],
                tags   : []
            }
        },

        methods: {
            refreshView: function() {
                let model = this.ui.showTags ? 'model+tags':'model';
                API.findPasswords({favourite: true}, model).then(this.updatePasswordList);
                API.findFolders({favourite: true}).then(this.updateFolderList);
                API.findTags({favourite: true}).then(this.updateTagList);
            }
        }
    };
</script>