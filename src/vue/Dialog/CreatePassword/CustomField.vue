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
    <div class="password-form-field-wrapper password-form-custom-field-wrapper" :class="{drag:drag}" @dragenter="dragEnter">
        <div class="area-label">
            <icon :icon="icon" @mouseenter="hover = true" @mouseleave="hover = false" @dragstart="dragStart" draggable="true"/>
            <input class="field-label" :placeholder="t('Name')" :maxlength="maxlength" v-model="model.label" required/>
        </div>
        <div class="area-options">
            <icon icon="trash" class="delete" @click="deleteField"/>
            <password-controls v-model="visible" v-on:generate="generatedPassword" v-if="value.type === 'secret'"/>
        </div>
        <text-custom-field :id="id" class="area-input" v-model="model" v-if="value.type === 'text'"/>
        <email-custom-field :id="id" class="area-input" v-model="model" v-if="value.type === 'email'"/>
        <secret-custom-field :id="id" class="area-input" v-model="model" :visible="visible" v-if="value.type === 'secret'"/>
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
    import Localisation from "@js/Classes/Localisation";
    import PasswordControls from "@vue/Dialog/CreatePassword/PasswordControls";
    import MessageService from "@js/Services/MessageService";
    import LocalisationService from "@js/Services/LocalisationService";

    export default {
        components: {PasswordControls, FileCustomField, DataCustomField, UrlCustomField, SecretCustomField, EmailCustomField, TextCustomField, Icon, Translate},
        extends   : AbstractField,
        inject    : ['dragService'],
        props     : {
            value: Object
        },
        data() {
            return {
                id     : `pw-custom-field-${this.value.type}-${Math.round(Math.random() * 10000)}`,
                visible: false,
                hover  : false
            };
        },
        computed: {
            icon() {
                if(this.hover) return 'bars';
                if(this.model.type === 'text') return 'font';
                if(this.model.type === 'secret') return 'lock';
                if(this.model.type === 'email') return 'envelope';
                if(this.model.type === 'url') return 'globe';
                if(this.model.type === 'file') return 'cloud';
                if(this.model.type === 'data') return 'file-archive-o';

                return 'question';
            },
            maxlength() {
                return 368 - this.model.value.length;
            },
            drag() {
                return this.dragService.isCurrent(this.model);
            }
        },
        methods : {
            deleteField() {
                MessageService
                    .confirm(['Do you want to delete the field "{field}"?', {field: this.model.label}], 'Delete field')
                    .then((success) => {
                        if(success) this.$emit('delete');
                    });
            },
            generatedPassword(password) {
                this.model.value = password;
                this.visible = true;
                this.$emit('input', this.model);
            },
            dragStart($event) {
                this.hover = false;
                this.dragService.start($event, this.model, this.$el);
            },
            dragEnter($event) {
                this.dragService.dragenter($event, this.model);
            }
        }
    };
</script>

<style lang="scss">
#content-vue.app-passwords .password-form-custom-field-wrapper {
    background-color : var(--color-main-background);
    border-radius    : var(--border-radius);

    .area-label {
        display     : flex;
        align-items : center;

        .icon {
            cursor : grab;
        }

        .field-label {
            margin     : 0;
            border     : none;
            font-weight  : bold;
            flex-grow    : 1;
            height     : auto;
            min-height : 0;
            padding    : 3px;

            &:hover,
            &:active,
            &:focus {
                border-color : var(--color-border-dark);
                font-weight  : normal;
            }
        }
    }

    .delete {
        cursor     : pointer;
        transition : color .15s ease-in-out;

        &:hover {
            color : var(--color-error);
        }
    }

    &.drag {
        background-color : var(--color-background-hover);
        margin           : -.5rem;
        padding          : .5rem;

        .area-label .field-label {
            background-color : var(--color-background-hover);
            border-color     : rgba(0, 0, 0, 0) !important;
        }
    }
}
</style>