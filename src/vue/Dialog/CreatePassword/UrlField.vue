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
    <div class="password-form-field-wrapper">
        <translate tag="label" :for="getId" :say="label" :icon="icon" class="area-label"/>
        <input :id="getId" type="text" class="area-input" :maxlength="maxlength" v-model="model" @blur="blur" :autocomplete="autocomplete">
    </div>
</template>

<script>
    import TextField from "@vue/Dialog/CreatePassword/TextField";

    export default {
        extends: TextField,
        methods: {
            blur() {
                let regex = /^[\w\-]+:.+$/;
                if(this.model.trim().length !== 0 && !regex.test(this.model)) {
                    this.model = 'https://' + this.model.trim();
                }
            }
        }
    };
</script>