<template>
    <div id="app-content" :class="{ 'show-details': showDetails, 'loading': loading }">
        <div class="app-content-left">
            <breadcrumb :showAddNew="false"/>
            <div class="item-list">
                <header-line :by="sort.by" :order="sort.order" v-on:updateSorting="updateSorting($event)" v-if="showHeader"/>
                <folder-line :folder="folder" v-for="folder in folders" :key="folder.id"/>
                <tag-line :tag="tag" v-for="tag in tags" :key="tag.id"/>
                <password-line :password="password" v-for="password in passwords" :key="password.id"/>
                <empty v-if="isEmpty" text="Your favorites will appear here"/>
            </div>
        </div>
        <div class="app-content-right">
            <password-details v-if="detail.type === 'password'" :password="detail.element"/>
        </div>
    </div>
</template>

<script>
    import API from '@js/Helper/api';
    import TagLine from '@vue/Line/Tag';
    import Events from "@js/Classes/Events";
    import Breadcrumb from '@vc/Breadcrumbs';
    import FolderLine from '@vue/Line/Folder';
    import Utility from "@js/Classes/Utility";
    import Empty from "@/vue/Components/Empty";
    import HeaderLine from "@/vue/Line/Header";
    import PasswordLine from '@vue/Line/Password';
    import PasswordDetails from '@vue/Details/Password';

    export default {
        data() {
            return {
                loading  : true,
                passwords: [],
                folders  : [],
                tags     : [],
                detail   : {
                    type   : 'none',
                    element: null
                },
                sort: {
                    by: 'label',
                    order: true
                }
            }
        },

        components: {
            Empty,
            TagLine,
            Breadcrumb,
            FolderLine,
            HeaderLine,
            PasswordLine,
            PasswordDetails
        },

        created() {
            this.refreshView();
            Events.on('data.changed', this.refreshView);
        },

        beforeDestroy() {
            Events.off('data.changed', this.refreshView)
        },

        computed: {
            showDetails() {
                return this.detail.type !== 'none';
            },
            showHeader() {
                return !this.loading && (this.passwords.length || this.tags.length || this.folders.length);
            },
            isEmpty() {
                return !this.loading && !this.passwords.length && !this.tags.length && !this.folders.length;
            }
        },

        methods: {
            refreshView: function() {
                API.findPasswords({favourite: true}).then(this.updatePasswordList);
                API.findFolders({favourite: true}).then(this.updateFolderList);
                API.findTags({favourite: true}).then(this.updateTagList);
            },
            updatePasswordList: function(passwords) {
                this.loading = false;
                this.passwords = Utility.sortApiObjectArray(passwords, this.sort.by, this.sort.order);
            },
            updateFolderList: function(folders) {
                this.loading = false;
                this.folders = Utility.sortApiObjectArray(folders, this.sort.by, this.sort.order);
            },
            updateTagList: function(tags) {
                this.loading = false;
                this.tags = Utility.sortApiObjectArray(tags, this.sort.by, this.sort.order);
            },
            updateSorting($event) {
                this.sort = $event;
                this.passwords = Utility.sortApiObjectArray(this.passwords, this.sort.by, this.sort.order);
                this.folders = Utility.sortApiObjectArray(this.folders, this.sort.by, this.sort.order);
                this.tags = Utility.sortApiObjectArray(this.tags, this.sort.by, this.sort.order);
            }
        }
    };
</script>