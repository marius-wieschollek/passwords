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
    <div class="password-form-field-wrapper password-form-add-field">
        <translate tag="label" :for="getId" say="Add field" icon="plus" class="area-label"/>
        <div class="area-input">
            <input type="text" :id="getId" v-model="label" maxlength="48"/>
            <custom-field-type v-model="type" :empty="true" :disabled="!hasLabel"/>
        </div>
    </div>
</template>

<script>
    import Translate from "@vc/Translate";
    import AbstractField from "@vue/Dialog/CreatePassword/AbstractField";
    import CustomFieldType from "@vue/Dialog/CreatePassword/CustomFields/CustomFieldType";

    export default {
        components: {CustomFieldType, Translate},
        extends   : AbstractField,
        data() {
            return {
                label: '',
                type : ''
            };
        },

        computed: {
            getId() {
                return 'password-add-field';
            },
            hasLabel() {
                return this.label.length > 0;
            }
        },
        watch   : {
            type(type) {
                if(type !== '') {
                    this.$emit('create', {label: this.label, type, value: ''});
                    this.$nextTick(() => {
                        this.label = '';
                        this.type = '';
                    });
                }
            }
        }
    };
</script>

<style lang="scss">
.password-form-add-field {
    .area-input {
        display               : grid;
        grid-template-columns : 3fr 1fr;

        input {
            width                      : 100%;
            border-top-right-radius    : 0;
            border-bottom-right-radius : 0;
        }

        select {
            border-top-left-radius    : 0;
            border-bottom-left-radius : 0;
            border-left               : 0;
        }
    }
}
</style>