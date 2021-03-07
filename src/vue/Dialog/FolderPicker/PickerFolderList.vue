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
    <ul class="folder-picker-list" v-if="folders.length">
        <li v-for="folder in folders" :key="folder.id" @click="$emit('navigate', folder)">{{ folder.label }}</li>
    </ul>
    <translate say="No content" v-else />
</template>

<script>
    import Translate from "@vc/Translate";
    export default {
        components: {Translate},
        props   : {
            folders: Array
        },
    };
</script>

<style lang="scss">
.folder-picker-list {
    li {
        background-image: var(--icon-folder-0082c9);
        background-repeat: no-repeat;
        line-height: 3rem;
        padding-left: 3rem;
        background-position: .5rem center;
        background-size: 2rem;
        cursor: pointer;
        border-bottom: 1px solid var(--color-border);

        &:hover {
            background-color: var(--color-background-hover)
        }
    }
}
</style>