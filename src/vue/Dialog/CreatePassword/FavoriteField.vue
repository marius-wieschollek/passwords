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
    <nc-button class="password-form-favorite" :title="t(title)" :aria-label="t(title)" type="tertiary" @click="model = !model">
        <star-icon class="favorite" data-item-action="favorite" fill-color="var(--color-warning)" slot="icon" v-if="model"/>
        <star-outline-icon class="favorite" data-item-action="favorite" fill-color="var(--color-warning)" slot="icon" v-else/>
    </nc-button>
</template>

<script>
    import AbstractField from '@vue/Dialog/CreatePassword/AbstractField';
    import NcButton from '@nc/NcButton';
    import StarIcon from '@icon/Star';
    import StarOutlineIcon from '@icon/StarOutline';

    export default {
        extends   : AbstractField,
        components: {StarOutlineIcon, StarIcon, NcButton},
        props     : {
            value: Boolean
        },
        computed  : {
            title() {
                return this.model ? 'Remove from favorites':'Mark as favorite';
            }
        }
    };
</script>

<style lang="scss">
.password-form-favorite {
    flex-grow : 0;
    margin    : .25rem 3rem 0 auto;
    position  : absolute;
    height    : 44px;
}
</style>