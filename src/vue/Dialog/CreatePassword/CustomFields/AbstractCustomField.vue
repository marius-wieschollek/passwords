<!--
  - @copyright 2022 Passwords App
  -
  - @author Marius David Wieschollek
  - @license AGPL-3.0
  -
  - This file is part of the Passwords App
  - created by Marius David Wieschollek.
  -->

<template>
    <div class="password-form-custom-field" v-if="isVisible">
        <input :type="inputType" :placeholder="inputPlaceholder" :id="id" v-model="data" v-bind="inputAttributes" v-on="inputEvents" required />
        <custom-field-type v-model="type" />
    </div>
</template>

<script>
    import CustomFieldType from '@vue/Dialog/CreatePassword/CustomFields/CustomFieldType';
    import Localisation    from '@js/Classes/Localisation';

    export default {
        components: {CustomFieldType},
        props     : {
            value: Object,
            id   : String
        },

        data() {
            return {
                data: this.value.value,
                type: this.value.type
            };
        },

        computed: {
            isVisible() {
                return true;
            },
            inputType() {
                return 'text';
            },
            inputPlaceholder() {
                return Localisation.translate('Value');
            },
            inputAttributes() {
                return {
                    maxlength: 368 - this.value.label.length
                };
            },
            inputEvents() {
                return {};
            }
        },

        methods: {
            emitUpdate() {
                this.$emit('input', {
                    label: this.value.label,
                    type : this.type,
                    value: this.data
                });
            }
        },

        watch: {
            value: {
                deep: true,
                handler(value) {
                    this.data = value.value;
                    this.type = value.type;
                }
            },
            data(value) {
                if(this.value.value !== value) {
                    this.emitUpdate();
                }
            },
            type(value) {
                if(this.value.type !== value) {
                    this.emitUpdate();
                }
            }
        }
    };
</script>

<style lang="scss">
#content-vue.app-passwords div.password-form-custom-field {
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
</style>