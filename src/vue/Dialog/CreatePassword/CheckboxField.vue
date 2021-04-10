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
    <div class="password-form-field-wrapper password-form-checkbox-wrapper">
        <translate tag="label" :for="getId" :say="label" :icon="icon" class="area-label"/>
        <input :id="getId" type="checkbox" v-model="model">
        <translate tag="label" :for="getId" :say="checkboxLabel" :icon="checkboxIcon" class="area-input"/>
    </div>
</template>

<script>
    import AbstractField from "@vue/Dialog/CreatePassword/AbstractField";
    import Translate from '@vc/Translate';

    export default {
        components: {Translate},
        extends   : AbstractField,
        props     : {
            id           : String,
            label        : String,
            icon         : String,
            checkboxLabel: String
        },
        computed  : {
            getId() {
                return `password-${this.id}`;
            },
            checkboxIcon() {
                console.log(this.model);
                return this.model ? 'check-square':'square-o';
            }
        }
    };
</script>

<style lang="scss">
.password-form-checkbox-wrapper {
    input {
        display : none;
    }

    .area-input .fa-check-square {
        color : var(--color-primary);
    }
}
</style>