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
    <div class="custom-field-form">
        <input type="text"
               :placeholder="namePlaceholder"
               class="field-label"
               v-model="label"
               maxlength="48"
               :class="{error:showNameError}"/>
        <select class="field-type" v-model="type" :disabled="!isValidName">
            <translate tag="option" value="text">Text</translate>
            <translate tag="option" value="secret">Secret</translate>
            <translate tag="option" value="email">Email</translate>
            <translate tag="option" value="url">Link</translate>
            <translate tag="option" value="file">File</translate>
        </select>
        <input class="file-picker"
               type="button"
               @click="openNextcloudFile"
               v-if="type === 'file'"
               :disabled="!isValidName"
               :style="getFileButtonStyle"
               :value="value"/>
        <input class="field-value"
               :type="getFieldType"
               :placeholder="valuePlaceholder"
               v-model="value"
               maxlength="320"
               v-if="type !== 'file'"
               :autocomplete="getAutoComplete"
               :disabled="!isValidName"
               :pattern="getPattern"
               required/>
        <input type="button" class="fa fa-trash field-button" @click="deleteField" :disabled="!isValidName" value=""/>
        <slot></slot>
    </div>
</template>

<script>
    import Messages from '@js/Classes/Messages';
    import Translate from '@vue/Components/Translate';
    import Localisation from '@js/Classes/Localisation';
    import SettingsService from '@js/Services/SettingsService';

    export default {
        components: {Translate},
        props     : {
            field: {
                type     : Object,
                'default': () => {
                    return {
                        label: '',
                        type : 'text',
                        id   : 0,
                        blank: false,
                        value: null
                    };
                }
            }
        },
        data() {
            return {
                label  : this.field.label,
                type   : this.field.type,
                value  : this.field.value,
                isBlank: this.field.blank
            };
        },

        computed: {
            showNameError() {
                return !this.isValidName && !this.isBlank;
            },
            isValidName() {
                return this.label.length;
            },
            getFieldType() {
                if(this.type === 'secret') return 'password';
                if(this.type === 'email') return 'email';
                if(this.type === 'url') return 'url';
                return 'text';
            },
            getFileButtonStyle() {
                return {
                    backgroundImage: `url(${SettingsService.get('server.theme.folder.icon')})`
                };
            },
            getPattern() {
                if(this.type === 'url') return '\\w+:\/\/.+';
                if(this.type === 'email') return '^.{1,}@[^@]{1,}$';
                return false;
            },
            getAutoComplete() {
                if(this.type === 'secret') return 'new-password';
                return 'on';
            },
            namePlaceholder() {
                return Localisation.translate('Name');
            },
            valuePlaceholder() {
                return Localisation.translate('Value');
            }
        },
        methods : {
            async openNextcloudFile() {
                this.value = await Messages.filePicker();
            },
            deleteField() {
                this.$emit('deleted', this.field);
            },
            updateField() {
                if(!this.value) return;

                this.$emit(
                    'updated',
                    {
                        label: this.label,
                        type : this.type,
                        value: this.value,
                        id   : this.field.id
                    }
                );
            }
        },

        watch: {
            label() {
                if(this.isValidName) this.updateField();
            },
            type(value) {
                if(value === 'file') this.value = null;
                if(this.isValidName) this.updateField();
            },
            value() {
                if(this.isValidName) this.updateField();
            },
            field(newValue) {
                this.label = newValue.label;
                this.type = newValue.type;
                this.value = newValue.value;
                this.isBlank = this.field.blank;
            }
        }
    };
</script>

<style lang="scss">
    #app-popup #passwords-create-new #custom-fields .custom-field-form {
        display               : grid;
        grid-template-areas   : "label type type" "value value options";
        grid-template-columns : auto 100px 35px;
        grid-column-gap       : 0.2rem;

        .field-label {
            grid-area : label;
            max-width : none;

            &.error {
                border : 1px solid var(--color-error);
            }
        }

        .field-type {
            grid-area : type;
            max-width : none;
        }

        .field-value,
        .file-picker {
            grid-area : value;
            max-width : none;
        }

        .file-picker {
            background    : no-repeat center;
            padding       : 0 25px;
            overflow      : hidden;
            text-overflow : ellipsis;

            &[value] {
                background-position : 0.75rem center;
            }
        }

        .field-button {
            grid-area     : options;
            max-width     : none;
            margin-right  : 0;
            border-radius : var(--border-radius-large);
        }
    }
</style>