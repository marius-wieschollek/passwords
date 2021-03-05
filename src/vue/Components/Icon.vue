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
    import Localisation from '@js/Classes/Localisation';

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
                return 'fa fa-' + this.icon + (this.spin ? ' fa-spin':'');
            },
            getTitle() {
                if(this.title) return Localisation.translate(this.title);
            }
        }
    };
</script>