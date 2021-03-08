<template>
    <dialog-window ref="window" id="passwords-edit-dialog" @drop.stop.prevent="dragDrop">
        <translate slot="title" :say="title"/>
        <div slot="window-controls">
            <favorite-field v-model="password.favorite"/>
        </div>
        <form class="password-form" id="password-edit-form" slot="content" v-on:submit.prevent="submitAction()">
            <div class="password-form-fields">
                <password-field v-model="password.password"/>
                <text-field v-model="password.username" id="username" label="Username" icon="user" maxlength="64"/>
                <text-field v-model="password.label" id="label" label="Name" icon="book" maxlength="64"/>
                <url-field v-model="password.url" id="url" label="Website" icon="globe" maxlength="2048"/>
                <folder-field v-model="password.folder" />
                <tags-field v-model="password.tags" v-if="false"/>

                <custom-field v-model="password.customFields[i]" v-on:delete="removeCustomField(i)" v-for="(customField, i) in password.customFields" :key="i"/>
                <new-custom-field @create="addCustomField" v-if="canAddField"/>
            </div>
            <notes-field v-model="password.notes"/>
            <encryption-options class="encryption-options" :password="password"/>
        </form>
        <div slot="controls" class="buttons">
            <div class="advanced-options">
                <encryption-options :password="password"/>
            </div>
            <translate class="btn primary btn-save" tag="input" type="submit" form="password-edit-form" localized-value="Save"/>
        </div>
    </dialog-window>

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
    import FolderField from "@vue/Dialog/CreatePassword/FolderField";
    import DialogWindow from "@vue/Dialog/DialogWindow";
    import TagsField from "@vue/Dialog/CreatePassword/TagsField";

    export default {
        components: {
            TagsField,
            DialogWindow,
            FolderField,
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
                        this.$refs.window.closeWindow();
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

        @media (max-width : $width-1366) {
            height    : 100%;
            max-width : 100vw;
            width     : 100vw;
        }
    }

    .content {
        .password-form {
            display        : flex;
            flex-direction : column;
            overflow-x     : auto;
            height         : 100%;

            .password-form-fields {
                display               : grid;
                grid-template-columns : 1fr 1fr 1fr;
                grid-column-gap       : 2rem;
                grid-row-gap          : 1rem;
                margin-bottom         : 2rem;

                @media all and (max-width : $width-1680) {
                    grid-template-columns : 1fr 1fr;
                }

                @media all and (max-width : $width-small) {
                    grid-template-columns : 1fr;
                }

                @media all and (min-width : $width-1680-above) {
                    .password-form-tags-wrapper {
                        grid-row-start    : 2;
                        grid-row-end      : 3;
                        grid-column-start : 3;
                        grid-column-end   : 4;
                    }
                    .password-form-folder-wrapper {
                        grid-row-start    : 1;
                        grid-row-end      : 2;
                        grid-column-start : 3;
                        grid-column-end   : 4;
                    }
                }
            }

            .encryption-options {
                display : none;
            }
        }

        @media (max-width : $width-extra-small) {
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
        }
    }

    .buttons {
        display : flex;

        .advanced-options {
            display     : flex;
            align-items : flex-end;
            flex-grow   : 1;

            @media (max-width : $width-extra-small) {
                display : none;
            }
        }

        .btn-save {
            font-size : 1.1rem;
            width     : 40%;

            @media (max-width : $width-extra-small) {
                width : 100%;
            }
        }
    }
}
</style>