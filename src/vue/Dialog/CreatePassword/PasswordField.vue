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
        <translate tag="label" for="password-password" say="Password" icon="key" class="area-label"/>
        <password-controls v-model="visible" v-on:generate="generatedPassword" class="area-options"/>
        <input id="password-password"
               :type="visible ? 'text':'password'"
               pattern=".{1,256}"
               autocomplete="new-password"
               v-model="model"
               :readonly="readonly"
               class="area-input"
               ref="input"
               required>
    </div>
</template>

<script>
    import AbstractField from '@vue/Dialog/CreatePassword/AbstractField';
    import Translate from '@vc/Translate';
    import PasswordControls from "@vue/Dialog/CreatePassword/PasswordControls";

    export default {
        components: {PasswordControls, Translate},
        extends   : AbstractField,
        props: {
            value: [String, Number, Boolean],
            autofocus: Boolean
        },

        data() {
            return {
                visible : false,
                readonly: true
            };
        },

        mounted() {
            this.$nextTick(() => {
                this.readonly = false;
                if(this.autofocus) {
                    this.$refs.input.focus();
                }
            });
        },

        methods: {
            generatedPassword(password) {
                this.model = password;
                this.visible = true;
            }
        }
    };
</script>