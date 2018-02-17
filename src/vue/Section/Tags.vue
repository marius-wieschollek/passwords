<template>
    <div id="app-content" :class="{ 'show-details': showDetails, 'loading': loading }">
        <div class="app-content-left">
            <breadcrumb :newTag="!currentTag" :newPassword="currentTag !== null" :tag="currentTag" :items="breadcrumb"/>
            <div class="item-list">
                <tag-line :tag="tag" v-for="tag in tags" :key="tag.id"/>
                <password-line :password="password" v-for="password in passwords" :key="password.id"/>
                <empty v-if="isEmpty"/>
            </div>
        </div>
        <div class="app-content-right">
            <password-details v-if="detail.type === 'password'" :password="detail.element"/>
        </div>
    </div>
</template>

<script>
    import API from '@js/Helper/api';
    import Events from "@js/Classes/Events";
    import Utility from "@js/Classes/Utility";
    import Breadcrumb from '@vc/Breadcrumbs.vue';
    import TagLine from '@vue/Line/Tag.vue';
    import PasswordLine from '@vue/Line/Password.vue';
    import PasswordDetails from '@vue/Details/Password.vue';
    import Empty from "@/vue/Components/Empty";

    export default {
        data() {
            return {
                loading     : true,
                currentTag  : null,
                defaultTitle: Utility.translate('Tags'),
                defaultPath : '/tags/',
                tags        : [],
                passwords   : [],
                detail      : {
                    type   : 'none',
                    element: null
                }
            };
        },

        components: {
            Empty,
            TagLine,
            Breadcrumb,
            PasswordLine,
            PasswordDetails
        },

        created() {
            this.refreshView();
            Events.on('tag.changed', this.refreshView);
            Events.on('password.changed', this.refreshView);
        },

        beforeDestroy() {
            Events.off('tag.changed', this.refreshView);
            Events.off('password.changed', this.refreshView);
        },

        computed: {
            showDetails() {
                return this.detail.type !== 'none';
            },
            isEmpty() {
                return !this.loading && !this.passwords.length && !this.tags.length;
            }
        },

        methods: {
            refreshView: function() {
                this.breadcrumb = [];

                this.loading = true;
                this.detail.type = 'none';
                if(this.$route.params.tag !== undefined) {
                    let tag = this.$route.params.tag;
                    this.tags = [];
                    this.currentTag = tag;
                    API.showTag(tag, 'model+passwords').then(this.updatePasswordList);
                } else {
                    this.passwords = [];
                    this.currentTag = null;
                    API.listTags().then(this.updateTagList);
                }
            },

            updateTagList: function(tags) {
                this.loading = false;
                this.tags = Utility.sortApiObjectArray(tags, 'label');
            },

            updatePasswordList: function(tag) {
                this.loading = false;
                if(tag.trashed) {
                    this.defaultTitle = Utility.translate('Trash');
                    this.defaultPath = '/trash';
                }

                this.passwords = Utility.sortApiObjectArray(tag.passwords, 'label');
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