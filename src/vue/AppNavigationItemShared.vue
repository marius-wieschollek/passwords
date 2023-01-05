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
    <app-navigation-item :title="t('Shares')" :to="{ name: 'Shares'}" :allowCollapse="true" :open="open">
        <share-variant-icon slot="icon"/>
        <template>
            <app-navigation-item :title="t('Shared with you')" :to="{ name: 'Shares', params: {type: '0'}}" :exact="true">
                <share-variant-icon slot="icon"/>
            </app-navigation-item>
            <app-navigation-item :title="t('Shared by you')" :to="{ name: 'Shares', params: {type: '1'}}" :exact="true">
                <share-variant-icon slot="icon"/>
            </app-navigation-item>
        </template>
    </app-navigation-item>
</template>
<script>
    import AppNavigationItem from "@vc/Navigation/AppNavigationItem";
    import ShareVariantIcon from "@icon/ShareVariant";

    export default {
        components: {AppNavigationItem, ShareVariantIcon},
        data() {
            return {
                open: false
            };
        },
        mounted() {
            this.open = this.$route.name === 'Shares';
        }
    };
</script>
