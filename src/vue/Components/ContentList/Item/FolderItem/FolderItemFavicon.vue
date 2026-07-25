<!--
  - @copyright 2026 Passwords App
  -
  - @author Marius David Wieschollek
  - @license AGPL-3.0
  -
  - This file is part of the Passwords App
  - created by Marius David Wieschollek.
  -->
<template>
    <div class="favicon">
        <img class="icon" :src="imgSrc" :title="title" width="32" height="32" loading="lazy" alt=""/>
        <star-icon class="favorite" :size="20" fill-color="var(--color-element-warning)" v-if="favorite"/>
    </div>
</template>

<script>
    import StarIcon from "@icon/Star.vue";
    import SettingsService from "@js/Services/SettingsService";
    import Favicon from "@vc/Favicon.vue";

    export default {
        components: {
            Favicon,
            StarIcon,
        },

        props: {
            favorite: {
                type: Boolean
            },
            title   : {
                type: String
            }
        },

        computed: {
            imgSrc() {
                return SettingsService.get('server.theme.folder.icon')
            }
        }
    };
</script>