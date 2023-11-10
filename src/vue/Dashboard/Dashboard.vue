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
    <ul class="passwords-widget" id="passwords-widget">
        <password-item :password="password" v-for="(password, id) in passwords" :key="id"/>
    </ul>
</template>

<script>
    import API from "@js/Helper/api";
    import PasswordItem from '@vc/Dashboard/PasswordItem';

    export default {
        components: {PasswordItem},
        data() {
            return {
                passwords: []
            };
        },
        mounted() {
            API.findPasswords({favorite: true})
               .then((passwords) => {this.passwords = passwords;});
        }
    };
</script>

<style lang="scss">
#passwords-widget {
    max-height : 100%;
    overflow   : auto;
}
</style>