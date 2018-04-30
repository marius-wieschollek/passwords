<template>
    <form class="custom-field-form">
        <input type="text" :placeholder="namePlaceholder" class="field-name" v-model="name" maxlength="48" :class="{error:!isValidName}"/>
        <select class="field-type" v-model="type" :disabled="!isValidName">
            <translate tag="option" value="text">Text</translate>
            <translate tag="option" value="secret">Secret</translate>
            <translate tag="option" value="email">Email</translate>
            <translate tag="option" value="url">Link</translate>
            <translate tag="option" value="file">File</translate>
        </select>
        <input class="file-picker" type="button" @click="openNextcloudFile" v-if="showFilePicker" :disabled="!isValidName" :style="getFileButtonStyle" :value="value"/>
        <input class="field-value" :type="getFieldType" :placeholder="valuePlaceholder" v-model="value" maxlength="320" v-if="!showFilePicker" :disabled="!isValidName"/>
        <button class="fa fa-undo field-button" @click="revertField" :disabled="isRevertable"></button>
        <button class="fa fa-trash field-button" @click="deleteField" :disabled="!isValidName"></button>
        <slot></slot>
    </form>
</template>

<script>
    import Messages from '@js/Classes/Messages';
    import Localisation from '@js/Classes/Localisation';
    import Translate from '@vue/Components/Translate';
    import SettingsManager from '@js/Manager/SettingsManager';

    export default {
        name      : 'custom-field-form',
        components: {Translate},
        props     : {
            field     : {
                type     : Object,
                'default': () => {
                    return {
                        name : '',
                        type : 'text',
                        value: null
                    };
                }
            },
            takenNames: {
                type     : Array,
                'default': () => {
                    return [];
                }
            },
            isBlank   : {
                type     : Boolean,
                'default': false
            }
        },
        data() {
            return {
                name        : this.field.name,
                type        : this.field.type,
                value       : this.field.value,
                originalName: this.field.name
            };
        },

        computed: {
            isValidName() {
                return this.name.length && (this.takenNames.indexOf(this.name) === -1 || this.originalName === this.name);
            },
            isRevertable() {
                return this.isValidName && this.field.name.length;
            },
            getFieldType() {
                if(this.type === 'secret') return 'password';
                if(this.type === 'email') return 'email';
                return 'text';
            },
            showFilePicker() {
                return this.type === 'file';
            },
            getFileButtonStyle() {
                return {
                    backgroundImage: `url(${SettingsManager.get('server.theme.folder.icon')})`
                };
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
            revertField() {
                this.name = this.field.name;
                this.type = this.field.type;
                this.value = this.field.value;
            },
            deleteField() {
                this.$emit('deleted', this.name);
            },
            updateField() {
                this.$emit(
                    'updated',
                    {
                        name        : this.name,
                        type        : this.type,
                        value       : this.value,
                        originalName: this.originalName
                    }
                );
                this.originalName = this.name;
            }
        },

        watch: {
            name() {
                if(this.isValidName) this.updateField();
            },
            type(value) {
                if(value === 'file') this.value = null;
                if(this.isValidName) this.updateField();
            },
            value() {
                if(this.isValidName) this.updateField();
            },
            isBlank(newValue, oldValue) {
                if(newValue && newValue !== oldValue) {
                    this.name = '';
                    this.type = 'text';
                    this.value = '';
                }
            },
            field(newValue) {
                if(newValue.name !== this.name) {
                    this.name = newValue.name;
                    this.originalName = newValue.name;
                }
                if(newValue.type !== this.type) this.type = newValue.type;
                if(newValue.value !== this.value) this.value = newValue.value;
            }
        }
    };
</script>

<style lang="scss">
    #app-popup #passwords-create-new #custom-fields .custom-field-form {
        .field-name {
            width     : 75.5%;
            max-width : none;

            &.error {
                border : 1px solid $color-red;
            }
        }

        .field-type {
            width     : 23%;
            max-width : none;
        }

        .field-value,
        .file-picker {
            width     : 85.5%;
            max-width : none;
        }

        .file-picker {
            background    : no-repeat center;
            padding       : 0 25px;
            overflow      : hidden;
            text-overflow : ellipsis;

            &[value] {
                background-position : 5px center;
            }
        }

        .field-button {
            width     : 6%;
            max-width : none;
        }
    }
</style>