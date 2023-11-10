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
        <favorites-view v-else/>
    </div>
</template>

<script>
    import Dashboard from '@js/Init/Dashboard';
    import FavoritesView from '@vc/Dashboard/FavoritesView';
    import AuthenticationView from '@vc/Dashboard/AuthenticationView';

    export default {
        components: {FavoritesView, AuthenticationView},
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