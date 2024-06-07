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
    <i :class="getIcon" v-on="$listeners" :title="getTitle" aria-hidden="true"></i>
</template>

<script>
    import LocalisationService from "@js/Services/LocalisationService";

    export default {
        props   : {
            icon: String,
            spin: {
                type: Boolean,
                default: false
            },
            title: {
                type: String,
                default: null
            }
        },
        computed: {
            getIcon() {
                return 'icon fa fa-' + this.icon + (this.spin ? ' fa-spin':'');
            },
            getTitle() {
                if(this.title) return LocalisationService.translate(this.title);
            }
        }
    };
</script>

<style lang="scss">
.icon.fa {
    width: 1em;
    text-align: center;
}
</style>