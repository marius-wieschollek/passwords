<template>
    <div id="app-content" :class="getContentClass">
        <div class="app-content-left">
            <breadcrumb :showAddNew="false" :items="breadcrumb"/>
            <div class="item-list">
                <header-line :field="sorting.field" :ascending="sorting.ascending" v-on:updateSorting="updateSorting($event)" v-if="showHeaderAndFooter"/>
                <security-line v-if="$route.params.status === undefined" v-for="(title, index) in securityStatus" :key="title" :status="index" :label="title"/>
                <password-line :password="password" v-for="password in passwords" :key="password.id"/>
                <footer-line :passwords="passwords" v-if="showHeaderAndFooter"/>
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
    import Breadcrumb from '@vc/Breadcrumb';
    import Utility from "@js/Classes/Utility";
    import Empty from "@vue/Components/Empty";
    import HeaderLine from "@vue/Line/Header";
    import FooterLine from "@vue/Line/Footer";
    import PasswordLine from '@vue/Line/Password';
    import SecurityLine from '@vue/Line/Security';
    import BaseSection from '@vue/Section/BaseSection';
    import PasswordDetails from '@vue/Details/Password';
    import Localisation from "@js/Classes/Localisation";

    export default {
        extends   : BaseSection,
        components: {
            Empty,
            Breadcrumb,
            HeaderLine,
            FooterLine,
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
            refreshView       : function() {
                this.detail.type = 'none';

                if(this.$route.params.status !== undefined) {
                    let status = this.$route.params.status,
                        label  = this.securityStatus[status],
                        model = this.ui.showTags ? 'model+tags':'model';

                    API.findPasswords({status: status}, model)
                        .then((d) => {this.updatePasswordList(d, status);});
                    if(!this.passwords.length) this.loading = true;

                    this.breadcrumb = [
                        {path: {name: 'Security'}, label: Localisation.translate('Security')},
                        {path: this.$route.path, label: Localisation.translate(label)}
                    ];
                } else {
                    this.loading = false;
                    this.passwords = [];
                    this.breadcrumb = [];
                }
            },
            updatePasswordList: function(passwords, status) {
                if(this.$route.params.status === status) {
                    this.loading = false;
                    this.passwords = Utility.sortApiObjectArray(passwords, this.getPasswordsSortingField(), this.sorting.ascending);
                }
            }
        },
        watch  : {
            $route: function() {
                this.refreshView();
            }
        }
    };
</script>