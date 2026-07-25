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
    <div class="actions-loading">
        <nc-loading-icon :size="34"/>
    </div>
</template>

<script>
    import NcLoadingIcon from '@nc/NcLoadingIcon.js';

    export default {
        components: {
            NcLoadingIcon
        }
    };
</script>

<style lang="scss">
#app-content {
    .item-list {
        .row {
            .actions-loading {
                display         : flex;
                align-self      : center;
                align-items     : center;
                justify-content : center;
            }
        }
    }
}
</style>