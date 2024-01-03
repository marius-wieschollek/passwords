<!--
  - @copyright 2023 Passwords App
  -
  - @author Marius David Wieschollek
  - @license AGPL-3.0
  -
  - This file is part of the Passwords App
  - created by Marius David Wieschollek.
  -->

<template>
    <div id="app-content" :class="getContentClass">
        <div class="app-content-left">
            <breadcrumb :showAddNew="false" :items="getBreadcrumb"/>
            <div class="item-list">
                <header-line :field="sorting.field" :ascending="sorting.ascending" v-on:updateSorting="updateSorting($event)" v-if="isNotEmpty"/>
                <security-line v-if="$route.params.status === undefined" v-for="(title, index) in securityStatus" :key="title" :status="index" :label="title"/>
                <password-line :password="password" v-for="password in passwords" :key="password.id"/>
                <footer-line :passwords="passwords" v-if="isNotEmpty"/>
                <empty v-if="isEmpty" :text="getEmptyText"/>
            </div>
        </div>
    </div>
</template>

<script>
    import API from '@js/Helper/api';
    import Breadcrumb from '@vc/Breadcrumb';
    import HeaderLine from '@vue/Line/Header';
    import FooterLine from '@vue/Line/Footer';
    import PasswordLine from '@vue/Line/Password';
    import SecurityLine from '@vue/Line/Security';
    import BaseSection from '@vue/Section/BaseSection';
    import Application from '@js/Init/Application';
    import UtilityService from "@js/Services/UtilityService";
    import LocalisationService from "@js/Services/LocalisationService";

    export default {
        extends   : BaseSection,
        components: {
            Breadcrumb,
            HeaderLine,
            FooterLine,
            PasswordLine,
            SecurityLine,
            'empty': () => import(/* webpackChunkName: "EmptyContent" */ '@vc/Empty')
        },
        data() {
            return {
                loading       : false,
                securityStatus: [
                    'Secure', 'Weak', 'Breached'
                ]
            };
        },

        computed: {
            isEmpty() {
                if(this.loading) return false;
                if(this.search.active && this.search.total === 0) return true;

                return !this.passwords.length && this.$route.params.status !== undefined;
            },
            isNotEmpty() {
                return !this.loading && !this.isEmpty && this.$route.params.status !== undefined;
            },
            getEmptyText() {
                if(this.search.active) {
                    return LocalisationService.translate('We could not find anything for "{query}"', {query: this.search.query});
                }

                return this.$route.params.status.toString() === '0' ? 'Better check the other sections':'That\'s probably a good sign';
            },
            getBreadcrumb() {
                if(this.$route.params.status !== undefined) {
                    let status = this.$route.params.status,
                        label  = this.securityStatus[status];

                    return [
                        {path: {name: 'Security'}, label: LocalisationService.translate('Security')},
                        {path: this.$route.path, label: LocalisationService.translate(label)}
                    ];
                }

                return [];
            }
        },

        methods: {
            refreshView       : function() {

                if(this.$route.params.status !== undefined) {
                    let status = parseInt(this.$route.params.status),
                        model  = this.ui.showTags ? 'model+tags':'model';

                    API.findPasswords({status: status}, model)
                       .then((d) => {this.updatePasswordList(d, status);})
                       .catch(console.error);
                    if(!this.passwords.length) this.loading = true;
                } else {
                    this.loading = false;
                    this.passwords = [];
                }
            },
            updatePasswordList: function(passwords, status) {
                if(parseInt(this.$route.params.status) === status) {
                    this.loading = false;
                    this.passwords = UtilityService.sortApiObjectArray(passwords, this.getPasswordsSortingField(), this.sorting.ascending);
                }
            }
        },
        watch  : {
            $route: function() {
                this.refreshView();
                Application.sidebar = null;
            }
        }
    };
</script>