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
    <app-navigation-item :title="t('Security')" :to="{ name: 'Security'}" v-if="isVisible" :allowCollapse="true" :open="open">
        <shield-half-full-icon slot="icon"/>
        <template>
            <app-navigation-item :title="t('Secure')" :to="{ name: 'Security', params: {status: '0'}}" :exact="true">
                <shield-half-full-icon fill-color="var(--color-success)" slot="icon"/>
            </app-navigation-item>
            <app-navigation-item :title="t('Weak')" :to="{ name: 'Security', params: {status: '1'}}" :exact="true">
                <shield-half-full-icon fill-color="var(--color-warning)" slot="icon"/>
            </app-navigation-item>
            <app-navigation-item :title="t('Breached')" :to="{ name: 'Security', params: {status: '3'}}" :exact="true">
                <shield-half-full-icon fill-color="var(--color-error)" slot="icon"/>
            </app-navigation-item>
        </template>
    </app-navigation-item>
</template>
<script>
    import AppNavigationItem from "@vc/Navigation/AppNavigationItem";
    import ShieldHalfFullIcon from "@icon/ShieldHalfFull";
    import SettingsService from "@js/Services/SettingsService";

    export default {
        components: {AppNavigationItem, ShieldHalfFullIcon},
        data() {
            let isHashEnabled = SettingsService.get('user.password.security.hash') > 0;
            return {
                isHashEnabled,
                open: false
            };
        },

        mounted() {
            this.open = this.$route.name === 'Security';
        },

        created() {
            SettingsService.observe('user.password.security.hash', (v) => { this.isHashEnabled = v.value > 0; });
        },

        computed: {
            isVisible() {
                return this.$route.name === 'Security' || this.isHashEnabled;
            }
        }
    };
</script>
