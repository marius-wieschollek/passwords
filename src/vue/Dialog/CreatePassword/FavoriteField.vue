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
    <icon class="password-form-favorite" :icon="icon" :title="title" @click="model = !model" />
</template>

<script>
    import Icon          from '@vc/Icon';
    import AbstractField from '@vue/Dialog/CreatePassword/AbstractField';

    export default {
        extends   : AbstractField,
        components: {Icon},
        props     : {
            value: Boolean
        },
        computed: {
            title() {
                return this.model ? 'Remove from favorites':'Mark as favorite';
            },
            icon() {
                return this.model ? 'star':'star-o';
            }
        }
    };
</script>

<style lang="scss">
.password-form-favorite {
    flex-grow : 0;
    margin    : 0 1rem 0 auto;
    color     : var(--color-warning);
    cursor    : pointer;
}
</style>