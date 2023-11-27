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
    <nc-text-field
            class="passwords-widget-search"
            :value.sync="value"
            :label="t('DashboardSearchLabel')"
            trailing-button-icon="close"
            :show-trailing-button="value !== ''"
            @trailing-button-click="clearText">
        <magnify-icon :size="20"/>
    </nc-text-field>
</template>

<script>
    import MagnifyIcon from '@icon/Magnify';
    import NcTextField from '@nc/NcTextField.js';

    export default {
        components: {NcTextField, MagnifyIcon},
        data() {
            return {
                value: ''
            };
        },
        inject : ['helper'],
        methods: {
            clearText() {
                this.value = '';
            }
        },
        watch  : {
            value(value) {
                this.helper.search(value);
            }
        }
    };
</script>

<style lang="scss">
.passwords-widget-search {
    margin-bottom : 1rem;
}
</style>