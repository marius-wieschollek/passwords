<template>
    <div class="background" id="passwords-edit-dialog" @drop.stop.prevent="dragDrop">
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
                        <url-field v-model="password.url" id="url" label="Website" icon="globe" maxlength="2048"/>

                        <custom-field v-model="password.customFields[i]" v-on:delete="removeCustomField(i)" v-for="(customField, i) in password.customFields" :key="i"/>
                        <new-custom-field @create="addCustomField" v-if="canAddField"/>
                    </div>
                    <notes-field v-model="password.notes"/>
                    <encryption-options class="encryption-options" :password="password"/>
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
    import PasswordField from '@vue/Dialog/CreatePassword/PasswordField';
    import TextField from '@vue/Dialog/CreatePassword/TextField';
    import NotesField from '@vue/Dialog/CreatePassword/NotesField';
    import EncryptionOptions from '@vue/Dialog/CreatePassword/EncryptionOptions';
    import FavoriteField from '@vue/Dialog/CreatePassword/FavoriteField';
    import NewCustomField from "@vue/Dialog/CreatePassword/NewCustomField";
    import CustomField from "@vue/Dialog/CreatePassword/CustomField";
    import UrlField from "@vue/Dialog/CreatePassword/UrlField";
    import CustomFieldsDragService from "@js/PasswordDialog/CustomFieldsDragService";

    export default {
        components: {
            UrlField,
            CustomField,
            NewCustomField,
            Icon,
            FavoriteField,
            EncryptionOptions,
            NotesField,
            TextField,
            PasswordField,
            Translate
        },

        provide() {
            return {
                dragService: this.dragService
            };
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
            let cseType     = SettingsService.get('user.encryption.cse') === 1 ? 'CSEv1r1':'none',
                password    = Object.assign({cseType, notes: '', favorite: false, customFields: []}, this.properties),
                dragService = new CustomFieldsDragService(password);

            return {password, dragService};
        },

        computed: {
            canAddField() {
                return this.password.customFields.length < 20;
            }
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
            removeCustomField(index) {
                this.password.customFields.splice(index, 1);
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
            },
            dragDrop($event) {
                this.dragService.end($event);
            }
        },

        watch: {
            password(password) {
                if(typeof password.customFields === 'string') password.customFields = JSON.parse(password.customFields);
                if(password.customFields === null) password.customFields = [];
                this.dragService.setPassword(password);
            }
        }
    };
</script>

<style lang="scss">
@import "~simplemde/dist/simplemde.min.css";

#app-popup #passwords-edit-dialog {
    .window {
        height    : 88%;
        max-width : 76vw;

        .title .close {
            margin-left : 0;
        }

        @media (max-width : $width-large) {
            height    : 100%;
            max-width : 100vw;
            width     : 100vw;
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
                margin-bottom         : 2rem;

                @media all and (max-width : $width-extra-large) {
                    grid-template-columns : 1fr 1fr;
                }

                @media all and (max-width : $width-small) {
                    grid-template-columns : 1fr;
                }
            }

            .encryption-options {
                display : none;
            }
        }

        .advanced-options {
            grid-area   : advanced;
            display     : flex;
            align-items : flex-end;
        }

        .controls {
            grid-area : controls;
            display   : flex;

            input {
                width     : 100%;
                font-size : 1.1rem;
            }
        }

        @media (max-width : $width-extra-small) {
            grid-template-areas   : "form" "controls";
            grid-template-columns : 1fr;

            .password-form {
                .encryption-options {
                    display     : flex;
                    align-items : center;
                    margin-top  : .5rem;

                    label {
                        flex-grow : 1;
                    }
                }
            }

            .advanced-options {
                display : none;
            }

            .controls {
                margin : .5rem 0 -.5rem;
            }
        }
    }
}
</style>