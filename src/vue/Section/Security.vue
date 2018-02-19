<template>
    <div id="app-content" :class="{ 'show-details': showDetails, 'loading': loading }">
        <div class="app-content-left">
            <breadcrumb :showAddNew="false" :items="breadcrumb"/>
            <div class="item-list">
                <header-line :by="sort.by" :order="sort.order" v-on:updateSorting="updateSorting($event)" v-if="showHeader"/>
                <security-line v-if="$route.params.status === undefined"
                               v-for="(title, index) in securityStatus"
                               :key="title"
                               :status="index"
                               :label="title">
                </security-line>
                <password-line :password="password" v-for="password in passwords" :key="password.id"/>
                <empty v-if="isEmpty" :text="emptyText"/>
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
    import Breadcrumb from '@vc/Breadcrumbs';
    import Utility from "@js/Classes/Utility";
    import Empty from "@/vue/Components/Empty";
    import HeaderLine from "@/vue/Line/Header";
    import PasswordLine from '@vue/Line/Password';
    import SecurityLine from '@vue/Line/Security';
    import PasswordDetails from '@vue/Details/Password';

    export default {
        components: {
            Empty,
            HeaderLine,
            Breadcrumb,
            PasswordLine,
            SecurityLine,
            PasswordDetails
        },
        data() {
            return {
                loading       : false,
                passwords     : [],
                breadcrumb    : [],
                detail        : {
                    type   : 'none',
                    element: null
                },
                securityStatus: [
                    'Secure', 'Weak', 'Broken'
                ],
                sort     : {
                    by   : 'label',
                    order: true
                }
            };
        },

        created() {
            this.refreshView();
            Events.on('password.changed', this.refreshView);
        },

        beforeDestroy() {
            Events.off('password.changed', this.refreshView);
        },

        computed: {
            showDetails() {
                return this.detail.type !== 'none';
            },
            showHeader() {
                return !this.loading && this.passwords.length;
            },
            isEmpty() {
                return !this.loading && !this.passwords.length && this.$route.params.status !== undefined;
            },
            emptyText() {
                if(this.$route.params.status.toString() === '0') {
                    return 'Better check the other sections';
                }
                return "That's probably a good sign";
            }
        },

        methods: {
            refreshView: function() {
                this.detail.type = 'none';
                if(this.$route.params.status !== undefined) {
                    let status = this.$route.params.status,
                        label  = this.securityStatus[status];
                    API.findPasswords({status: status}).then(this.updateContentList);

                    if(this.passwords.length === 0) this.loading = true;
                    this.breadcrumb = [
                        {path: '/security', label: Utility.translate('Security')},
                        {path: this.$route.path, label: Utility.translate(label)}
                    ];
                } else {
                    this.passwords = [];
                    this.breadcrumb = [];
                }
            },

            updateContentList: function(passwords) {
                this.loading = false;
                this.passwords = Utility.sortApiObjectArray(passwords, this.sort.by, this.sort.order);
            },

            updateSorting($event) {
                this.sort = $event;
                this.passwords = Utility.sortApiObjectArray(this.passwords, this.sort.by, this.sort.order);
            }
        },
        watch  : {
            $route: function() {
                this.refreshView();
            }
        }
    };
</script>