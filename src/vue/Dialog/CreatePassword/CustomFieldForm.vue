<template>
    <form class="custom-field-form">
        <input type="text" placeholder="Name" class="field-name" v-model="name" maxlength="48" :class="{error:!isValidName}">
        <select class="field-type" v-model="type" :disabled="!isValidName">
            <translate tag="option" value="text">Text</translate>
            <translate tag="option" value="secret">Geheimnis</translate>
            <translate tag="option" value="email">Email</translate>
            <translate tag="option" value="url">Link</translate>
            <translate tag="option" value="file">File</translate>
        </select>
        <button class="fa fa-folder file-picker" @click="openNextcloudFile" v-if="showFilePicker" :disabled="!isValidName">{{value}}</button>
        <input class="field-value" :type="getFieldType" placeholder="Value" v-model="value" maxlength="320" v-if="!showFilePicker" :disabled="!isValidName"/>
        <button class="fa fa-undo field-button" @click="revertField" :disabled="isRevertable"></button>
        <button class="fa fa-trash field-button" @click="deleteField" :disabled="!isValidName"></button>
        <slot></slot>
    </form>
</template>

<script>
    import Messages from '@js/Classes/Messages';
    import Translate from "@/vue/Components/Translate";

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
                'default': []
            }
        },
        data() {
            return {
                name : this.field.name,
                type : this.field.type,
                value: this.field.value
            };
        },

        computed: {
            isValidName() {
                return this.name.length &&
                       this.name.substr(0, 1) !== '_' &&
                       (this.takenNames.indexOf(this.name) === -1 || this.field.name === this.name);
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
                return this.isValidName && this.type === 'file';
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
            updateField(oldValue = '') {
                this.$emit(
                    'updated',
                    {
                        name        : this.name,
                        type        : this.type,
                        value       : this.value,
                        originalName: oldValue
                    }
                );
            }
        },

        watch: {
            name(value, oldValue) {
                if(this.isValidName) this.updateField(oldValue)
            },
            type() {
                if(this.isValidName) this.updateField()
            },
            value() {
                if(this.isValidName) this.updateField()
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
                border: 1px solid $color-red;
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

        .field-button {
            width     : 6%;
            max-width : none;
        }
    }
</style>