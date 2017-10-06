<template>
    <div id="app-content" v-bind:class="{ 'show-details': showDetails }">
        <div class="app-content-left">
            <breadcrumb :showAddNew="false" :items="breadcrumb"></breadcrumb>
            <div class="item-list">
                <security-line v-if="$route.params.status === undefined"
                               v-for="(title, index) in securityStatus"
                               :key="title"
                               :status="index"
                               :label="title">
                </security-line>
                <password-line :password="password" v-for="password in passwords" :key="password.uuid"></password-line>
            </div>
        </div>
        <div class="app-content-right">
            <password-details v-if="detail.type == 'password'" :password="detail.element"></password-details>
        </div>
    </div>
</template>

<script>
    import PwEvents from "@js/Classes/Events";
    import Utility from "@js/Classes/Utility";
    import Breadcrumb from '@vc/Breadcrumbs.vue';
    import PasswordLine from '@vc/Line/Password.vue';
    import SecurityLine from '@vc/Line/Security.vue';
    import PasswordDetails from '@vc/Details/Password.vue';
    import API from '@js/Helper/api';

    export default {
        components: {
            Breadcrumb,
            PasswordDetails,
            PasswordLine,
            SecurityLine
        },
        data() {
            return {
                passwords     : [],
                detail        : {
                    type   : 'none',
                    element: null
                },
                securityStatus: [
                    'Secure', 'Weak', 'Broken'
                ],
                breadcrumb: []
            }
        },

        created() {
            this.refreshView();
            PwEvents.on('data.changed', this.refreshView);
        },

        beforeDestroy() {
            PwEvents.off('data.changed', this.refreshView)
        },

        computed: {
            showDetails() {
                return this.detail.type !== 'none';
            }
        },
        watch   : {
            $route: function () {
                this.refreshView()
            }
        },

        methods: {
            refreshView: function () {
                if (this.$route.params.status !== undefined) {
                    let status = this.$route.params.status,
                        label = this.securityStatus[status];
                    API.findPasswords({status: status}).then(this.updateContentList);
                    this.breadcrumb = [
                        {path: '/show/security', label: Utility.translate('Security')},
                        {path: this.$route.path, label: Utility.translate(label)}
                    ]
                } else {
                    this.passwords = {};
                    this.breadcrumb = [];
                }
            },

            updateContentList: function (passwords) {
                this.passwords = Utility.sortApiObjectArray(passwords, 'title', true);
            }
        }
    };
</script>