<template>
    <div id="app-content" :class="{ 'show-details': showDetails, 'loading': loading }">
        <div class="app-content-left">
            <breadcrumb :showAddNew="false" :items="breadcrumb"/>
            <div class="item-list">
                <security-line v-if="$route.params.status === undefined"
                               v-for="(title, index) in securityStatus"
                               :key="title"
                               :status="index"
                               :label="title">
                </security-line>
                <password-line :password="password" v-for="password in passwords" :key="password.id"/>
            </div>
        </div>
        <div class="app-content-right">
            <password-details v-if="detail.type === 'password'" :password="detail.element"/>
        </div>
    </div>
</template>

<script>
    import Events from "@js/Classes/Events";
    import Utility from "@js/Classes/Utility";
    import Breadcrumb from '@vc/Breadcrumbs.vue';
    import PasswordLine from '@vue/Line/Password.vue';
    import SecurityLine from '@vue/Line/Security.vue';
    import PasswordDetails from '@vue/Details/Password.vue';
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
                loading       : false,
                passwords     : [],
                detail        : {
                    type   : 'none',
                    element: null
                },
                securityStatus: [
                    'Secure', 'Weak', 'Broken'
                ],
                breadcrumb    : []
            }
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
                if (this.$route.params.status !== undefined) {
                    let status = this.$route.params.status,
                        label  = this.securityStatus[status];
                    API.findPasswords({status: status}).then(this.updateContentList);

                    if (this.passwords.length === 0) this.loading = true;
                    this.breadcrumb = [
                        {path: '/security', label: Utility.translate('Security')},
                        {path: this.$route.path, label: Utility.translate(label)}
                    ]
                } else {
                    this.passwords = [];
                    this.breadcrumb = [];
                }
            },

            updateContentList: function (passwords) {
                this.loading = false;
                this.passwords = Utility.sortApiObjectArray(passwords, 'label', true);
            }
        },
        watch  : {
            $route: function () {
                this.refreshView()
            }
        }
    };
</script>