<template>
    <div id="app-content" :class="getContentClass">
        <div class="app-content-left">
            <breadcrumb/>
            <div class="item-list">
                <header-line :by="sort.by" :order="sort.order" v-on:updateSorting="updateSorting($event)" v-if="showHeaderAndFooter"/>
                <password-line :password="password" v-for="password in passwords" :key="password.id"/>
                <footer-line :passwords="passwords" v-if="showHeaderAndFooter"/>
                <empty v-if="isEmpty"/>
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
            Breadcrumb,
            HeaderLine,
            FooterLine,
            PasswordLine,
            PasswordDetails
        },

        data() {
            return {
                sort: {
                    by   : 'edited',
                    order: false
                }
            }
        },

        methods: {
            refreshView: function() {
                API.listPasswords().then(this.updateContentList);
            },

            updateContentList: function(passwords) {
                let array = Utility.sortApiObjectArray(passwords, 'edited', false);
                this.loading = false;
                this.passwords = Utility.sortApiObjectArray(array.slice(0, 15), this.sort.by, this.sort.order);
            }
        }
    };
</script>