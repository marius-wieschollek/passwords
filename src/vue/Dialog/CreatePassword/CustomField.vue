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
        <label :for="id" class="area-label">
            <icon :icon="icon"/>
            {{ model.label }}
        </label>
        <div class="area-options">
            <icon icon="trash-o" class="delete" @click="deleteField"/>
        </div>
        <text-custom-field :id="id" class="area-input" v-model="model" v-if="value.type === 'text'"/>
        <email-custom-field :id="id" class="area-input" v-model="model" v-if="value.type === 'email'"/>
        <secret-custom-field :id="id" class="area-input" v-model="model" v-if="value.type === 'secret'"/>
        <url-custom-field :id="id" class="area-input" v-model="model" v-if="value.type === 'url'"/>
        <file-custom-field :id="id" class="area-input" v-model="model" v-if="value.type === 'file'"/>
        <data-custom-field :id="id" class="area-input" v-model="model" v-if="value.type === 'data'"/>
    </div>
</template>

<script>
    import Icon from "@vc/Icon";
    import Translate from "@vc/Translate";
    import AbstractField from "@vue/Dialog/CreatePassword/AbstractField";
    import TextCustomField from "@vue/Dialog/CreatePassword/CustomFields/TextCustomField";
    import EmailCustomField from "@vue/Dialog/CreatePassword/CustomFields/EmailCustomField";
    import SecretCustomField from "@vue/Dialog/CreatePassword/CustomFields/SecretCustomField";
    import UrlCustomField from "@vue/Dialog/CreatePassword/CustomFields/UrlCustomField";
    import DataCustomField from "@vue/Dialog/CreatePassword/CustomFields/DataCustomField";
    import FileCustomField from "@vue/Dialog/CreatePassword/CustomFields/FileCustomField";
    import Messages from "@js/Classes/Messages";

    export default {
        components: {FileCustomField, DataCustomField, UrlCustomField, SecretCustomField, EmailCustomField, TextCustomField, Icon, Translate},
        extends   : AbstractField,
        props     : {
            value: Object
        },
        data() {
            return {
                id: `ps-custom-field-${this.value.type}-${Math.round(Math.random() * 10000)}`
            };
        },
        computed: {
            icon() {
                if(this.model.type === 'text') return 'font';
                if(this.model.type === 'secret') return 'lock';
                if(this.model.type === 'email') return 'envelope';
                if(this.model.type === 'url') return 'globe';
                if(this.model.type === 'file') return 'cloud';
                if(this.model.type === 'data') return 'file-archive-o';

                return 'question';
            }
        },
        methods : {
            deleteField() {
                Messages
                    .confirm(['Do you want to delete the field "{field}"?', {field: this.model.label}], 'Delete field')
                    .then((success) => {
                        if(success) this.$emit('delete');
                    });
            }
        }
    };
</script>

<style lang="scss">
.password-form-field-wrapper {
    .delete {
        cursor     : pointer;
        transition : color .15s ease-in-out;

        &:hover {
            color : var(--color-error);
        }
    }
}
</style>