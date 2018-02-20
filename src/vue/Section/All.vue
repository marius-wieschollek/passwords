<template>
    <div id="app-content" :class="getContentClass">
        <div class="app-content-left">
            <breadcrumb/>
            <div class="item-list">
                <header-line :by="sort.by" :order="sort.order" v-on:updateSorting="updateSorting($event)" v-if="showHeader"/>
                <password-line :password="password" v-for="password in passwords" :key="password.id"/>
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
    import Empty from "@/vue/Components/Empty";
    import HeaderLine from "@/vue/Line/Header";
    import PasswordLine from '@vue/Line/Password';
    import BaseSection from '@vue/Section/BaseSection';
    import PasswordDetails from '@vue/Details/Password';

    export default {
        extends: BaseSection,

        components: {
            Empty,
            HeaderLine,
            Breadcrumb,
            PasswordLine,
            PasswordDetails
        },

        methods: {
            refreshView: function() {
                API.listPasswords().then(this.updatePasswordList);
            }
        }
    }
</script>