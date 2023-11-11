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
    <div class="passwords-widget" id="passwords-widget">
        <authentication-view v-if="!isAuthorized"/>
        <passwords-view v-else/>
    </div>
</template>

<script>
    import Dashboard from '@js/Init/Dashboard';
    import PasswordsView from '@vc/Dashboard/PasswordsView';
    import AuthenticationView from '@vc/Dashboard/AuthenticationView';

    export default {
        components: {PasswordsView, AuthenticationView},
        props     : {
            api: {
                type: Object
            }
        },
        provide() {
            return {
                api: this.api
            };
        },
        computed: {
            isAuthorized() {
                return Dashboard.isAuthorized;
            }
        }
    };
</script>

<style lang="scss">
#passwords-widget {
    height: 100%;
}
</style>