<template>
    <div id="app-content" v-bind:class="{ 'show-details': showDetails }">
        <div class="app-content-left">
            <breadcrumb :newTag="true"></breadcrumb>
            <div class="item-list">
                <tag-line :tag="tag" v-for="tag in tags" :key="tag.id"></tag-line>
                <password-line :password="password" v-for="password in passwords" :key="password.id"></password-line>
            </div>
        </div>
        <div class="app-content-right">
            <password-details v-if="detail.type == 'password'" :password="detail.element"></password-details>
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

    export default {
        data() {
            return {
                tags     : [],
                passwords: [],
                detail   : {
                    type   : 'none',
                    element: null
                }
            }
        },

        components: {
            TagLine,
            Breadcrumb,
            PasswordLine,
            PasswordDetails
        },

        created() {
            this.refreshView();
            Events.on('password.changed', this.refreshView);
        },

        beforeDestroy() {
            Events.off('password.changed', this.refreshView)
        },

        computed: {
            showDetails() {
                return this.detail.type !== 'none';
            }
        },

        methods: {
            refreshView: function () {
                API.listTags().then(this.updateContentList);
            },

            updateContentList: function (tags) {
                this.tags = Utility.sortApiObjectArray(tags, 'label', true);
            }
        }
    };
</script>