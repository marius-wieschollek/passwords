<!--
  - @copyright 2021 Passwords App
  -
  - @author Marius David Wieschollek
  - @license AGPL-3.0
  -
  - This file is part of the Passwords App
  - created by Marius David Wieschollek.
  -->

<template>
    <i :class="getIcon" aria-hidden="true"></i>
</template>

<script>
    export default {
        props   : {
            icon: String
        },
        computed: {
            getIcon() {
                return 'fa fa-' + this.icon;
            }
        }
    };
</script>

<style scoped>

</style>