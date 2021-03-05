<template>
    <div class="background" id="passwords-create-new">
        <div class="window">
            <div class="title">
                <translate :say="title"/>
                <favorite-field v-model="password.favorite"/>
                <icon icon="close" class="close" title="Close" @click="closeWindow()"/>
            </div>
            <form class="content" v-on:submit.prevent="submitAction()">
                <div class="password-form">
                    <div class="password-form-fields">
                        <password-field v-model="password.password"/>
                        <text-field v-model="password.username" id="username" label="Username" icon="user" maxlength="64"/>
                        <text-field v-model="password.label" id="label" label="Name" icon="book" maxlength="64"/>
                        <text-field v-model="password.url" id="url" label="Website" icon="globe" maxlength="2048"/>

                        <custom-field v-model="password.customFields[i]" v-for="(customField, i) in password.customFields" :key="i"/>
                        <new-custom-field @create="addCustomField"/>
                    </div>
                    <notes-field v-model="password.notes"/>
                </div>

                <div class="advanced-options">
                    <encryption-options :password="password"/>
                </div>

                <div class="controls">
                    <translate class="btn primary" tag="input" type="submit" localized-value="Save"/>
                </div>
            </form>
        </div>
    </div>
</template>

<script>
    import API from '@js/Helper/api';
    import Translate from '@vc/Translate';
    import Icon from '@vc/Icon';
    import Utility from '@js/Classes/Utility';
    import SettingsService from '@js/Services/SettingsService';
    import CustomFields from '@vue/Dialog/CreatePassword/CustomFields';
    import PasswordField from '@vue/Dialog/CreatePassword/PasswordField';
    import TextField from '@vue/Dialog/CreatePassword/TextField';
    import NotesField from '@vue/Dialog/CreatePassword/NotesField';
    import EncryptionOptions from '@vue/Dialog/CreatePassword/EncryptionOptions';
    import FavoriteField from '@vue/Dialog/CreatePassword/FavoriteField';
    import NewCustomField from "@vue/Dialog/CreatePassword/NewCustomField";
    import CustomField from "@vue/Dialog/CreatePassword/CustomField";

    export default {
        components: {
            CustomField,
            NewCustomField,
            Icon,
            FavoriteField,
            EncryptionOptions,
            NotesField,
            TextField,
            PasswordField,
            Translate,
            CustomFields
        },

        props: {
            title     : {
                type     : String,
                'default': 'Create password'
            },
            properties: {
                type: Object
            },
            _success  : {
                type: Function
            }
        },

        data() {
            let cseType  = SettingsService.get('user.encryption.cse') === 1 ? 'CSEv1r1':'none',
                password = Object.assign({cseType, notes: '', favorite: false, customFields: []}, this.properties);

            return {password};
        },

        mounted() {
        },

        methods: {
            closeWindow() {
                this.$destroy();
                let container = document.getElementById('app-popup'),
                    div       = document.createElement('div');
                container.replaceChild(div, container.childNodes[0]);
            },
            addCustomField($event) {
                this.password.customFields.push($event);
            },
            submitAction() {
                let password = Utility.cloneObject(this.password);
                password = API.flattenPassword(password);
                password = API.validatePassword(password);

                if(this._success) {
                    try {
                        this._success(password);
                        this.closeWindow();
                    } catch(e) {
                        console.error(e);
                    }
                }
            }
        },

        watch: {
            password(password) {
                if(typeof password.customFields === 'string') password.customFields = JSON.parse(password.customFields);
                if(password.customFields === null) password.customFields = [];
            }
        }
    };
</script>

<style lang="scss">
@import "~simplemde/dist/simplemde.min.css";

#app-popup #passwords-create-new {
    .window {
        height : 88%;

        .title .close {
            margin-left : 0;
        }

        @media (max-width : $width-medium) {
            height : 100%;
        }
    }

    .content {
        display               : grid;
        grid-template-rows    : auto 3rem;
        grid-template-columns : 1fr 1fr;
        grid-template-areas   : "form form" "advanced controls";
        padding               : 1rem;

        .password-form {
            display        : flex;
            flex-direction : column;
            grid-area      : form;
            overflow-x     : auto;

            .password-form-fields {
                display               : grid;
                grid-template-columns : 1fr 1fr 1fr;
                grid-column-gap       : 2rem;
                grid-row-gap          : 1rem;
                margin-bottom         : 1rem;
            }
        }

        .advanced-options {
            grid-area : advanced;
        }

        .controls {
            grid-area : controls;
            display   : flex;

            input {
                width     : 100%;
                font-size : 1.1rem;
            }
        }
    }
}
</style>