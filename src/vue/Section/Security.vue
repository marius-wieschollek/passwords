<template>
    <div id="app-content" :class="getContentClass">
        <div class="app-content-left">
            <breadcrumb :showAddNew="false" :items="breadcrumb"/>
            <div class="item-list">
                <header-line :by="sort.by" :order="sort.order" v-on:updateSorting="updateSorting($event)" v-if="showHeader"/>
                <security-line v-if="$route.params.status === undefined" v-for="(title, index) in securityStatus" :key="title" :status="index" :label="title">
                </security-line>
                <password-line :password="password" v-for="password in passwords" :key="password.id"/>
                <empty v-if="isEmpty" :text="emptyText"/>
            </div>
        </div>
        <div class="app-content-right">
            <password-details v-if="showPasswordDetails" :password="detail.element"/>
        </div>
    </div>
</template>

<script>
    import API from '@js/Helper/api';
    import Breadcrumb from '@vc/Breadcrumbs';
    import Utility from "@js/Classes/Utility";
    import Empty from "@/vue/Components/Empty";
    import HeaderLine from "@/vue/Line/Header";
    import PasswordLine from '@vue/Line/Password';
    import SecurityLine from '@vue/Line/Security';
    import BaseSection from '@vue/Section/BaseSection';
    import PasswordDetails from '@vue/Details/Password';

    export default {
        extends   : BaseSection,
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
                breadcrumb    : [],
                securityStatus: [
                    'Secure', 'Weak', 'Broken'
                ]
            };
        },

        computed: {
            isEmpty() {
                return !this.loading && !this.passwords.length && this.$route.params.status !== undefined;
            },
            emptyText() {
                return this.$route.params.status.toString() === '0' ? 'Better check the other sections':'That\'s probably a good sign';
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
        },
        watch  : {
            $route: function() {
                this.refreshView();
            }
        }
    };
</script>