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
        <tag-icon :size="32" :fill-color="color" :title="title"/>
        <star-icon class="favorite" :size="20" fill-color="var(--color-element-warning)" v-if="favorite"/>
    </div>
</template>

<script>
    import StarIcon from "@icon/Star.vue";
    import TagIcon from "@icon/Tag.vue";

    export default {
        components: {
            TagIcon,
            StarIcon
        },

        props: {
            favorite: {
                type: Boolean
            },
            color   : {
                type: String
            },
            title   : {
                type: String
            }
        }
    };
</script>