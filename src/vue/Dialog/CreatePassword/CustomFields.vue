<template>
    <div class="custom-fields" id="custom-fields">
        <div v-for="(field, name) in fields">{{name}} {{field.value}}</div>

        <form class="custom-field-form">
            <input type="text" placeholder="Name" class="field-name" v-model="field.name" maxlength="48">
            <select class="field-type" v-model="field.type" :disabled="!isValidName">
                <option value="text">Text</option>
                <option value="password">Password</option>
                <option value="email">E-Mail</option>
                <option value="url">Link</option>
                <option value="file">File</option>
            </select>
            <button class="fa fa-folder file-picker" @click="openNextcloudFile" v-if="showFilePicker" :disabled="!isValidName">{{field.value}}</button>
            <input class="field-value" :type="getFieldType" placeholder="Value" v-model="field.value" maxlength="320" v-if="!showFilePicker" :disabled="!isValidName"/>
            <button class="fa fa-plus button-save" @click="addCustomField" :disabled="!field.value || !field.value.length"></button>
        </form>
    </div>
</template>

<script>
    import Messages from '@js/Classes/Messages';

    export default {
        props   : {
            fields: {
                type: Object
            }
        },
        data() {
            return {
                field: {
                    name : '',
                    type : 'text',
                    value: null
                },
                file : null
            };
        },
        computed: {
            isValidName() {
                return !this.fields.hasOwnProperty(this.field.name) && this.field.name.length && this.field.name.substr(0, 1) !== '_';
            },
            getFieldType() {
                if(['password', 'email'].indexOf(this.field.type) !== -1) return this.field.type;
                return 'text';
            },
            showFilePicker() {
                return this.isValidName && this.field.type === 'file';
            }
        },
        methods : {
            async openNextcloudFile() {
                this.field.value = await Messages.filePicker();
            },
            addCustomField() {
                this.fields[this.field.name] = {
                    type: this.field.type,
                    value: this.field.value,
                };
                this.field = {
                    name : '',
                    type : 'text',
                    value: null
                };
            }
        }
    };
</script>

<style lang="scss">
    #app-popup #passwords-create-new #custom-fields {
        .custom-field-form {
            .field-name {
                width     : 75.5%;
                max-width : none;
            }

            .field-type {
                width     : 23%;
                max-width : none;
            }

            .field-value,
            .file-picker {
                width     : 92.5%;
                max-width : none;
            }

            .button-save {
                width     : 6%;
                max-width : none;
            }
        }
    }
</style>