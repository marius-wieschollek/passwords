<!--
  - @copyright 2023 Passwords App
  -
  - @author Marius David Wieschollek
  - @license AGPL-3.0
  -
  - This file is part of the Passwords App
  - created by Marius David Wieschollek.
  -->

<template>
    <nc-modal id="passwords-edit-dialog" :container="container" size="large" @drop.stop.prevent="dragDrop" v-on:close="closeWindow">
        <div class="header">
            <translate tag="h1" :say="title" />
            <favorite-field v-model="password.favorite" />
        </div>
        <div class="content">
            <form class="password-form" id="password-edit-form" v-on:submit.prevent="submitAction()">
                <div class="password-form-fields">
                    <password-field v-model="password.password" />
                    <text-field v-model="password.username" id="username" label="Username" icon="user" maxlength="64" />
                    <text-field v-model="password.label" id="label" label="Name" icon="book" maxlength="64" />
                    <url-field v-model="password.url" id="url" label="Website" icon="globe" maxlength="2048" />
                    <folder-field v-model="password.folder" v-on:folder="updateFolder" />
                    <tags-field v-model="password.tags" />
                    <checkbox-field v-model="password.hidden"
                                    id="hidden"
                                    label="Hidden password"
                                    checkbox-label="Don't show this password anywhere"
                                    icon="eye-slash"
                                    v-if="showHidden" />

                    <custom-field v-model="password.customFields[i]" v-on:delete="removeCustomField(i)" v-for="(customField, i) in password.customFields" :key="i" />
                    <new-custom-field @create="addCustomField" v-if="canAddField" />
                </div>
                <notes-field v-model="password.notes" />
                <encryption-options class="encryption-options" :password="password" />
            </form>
        </div>
        <div class="buttons">
            <div class="advanced-options">
                <encryption-options :password="password" />
            </div>
            <nc-button class="password-form-favorite btn-save" type="primary" form="password-edit-form" nativeType="submit" :wide="true">
                {{ t('Save') }}
            </nc-button>
        </div>
    </nc-modal>
</template>

<script>
    import API                     from '@js/Helper/api';
    import Translate               from '@vc/Translate';
    import Icon                    from '@vc/Icon';
    import Utility                 from '@js/Classes/Utility';
    import SettingsService         from '@js/Services/SettingsService';
    import PasswordField           from '@vue/Dialog/CreatePassword/PasswordField';
    import TextField               from '@vue/Dialog/CreatePassword/TextField';
    import NotesField              from '@vue/Dialog/CreatePassword/NotesField';
    import EncryptionOptions       from '@vue/Dialog/CreatePassword/EncryptionOptions';
    import FavoriteField           from '@vue/Dialog/CreatePassword/FavoriteField';
    import NewCustomField          from '@vue/Dialog/CreatePassword/NewCustomField';
    import CustomField             from '@vue/Dialog/CreatePassword/CustomField';
    import UrlField                from '@vue/Dialog/CreatePassword/UrlField';
    import CustomFieldsDragService from '@js/PasswordDialog/CustomFieldsDragService';
    import FolderField             from '@vue/Dialog/CreatePassword/FolderField';
    import TagsField               from '@vue/Dialog/CreatePassword/TagsField';
    import CheckboxField           from '@vue/Dialog/CreatePassword/CheckboxField';
    import Messages                from '@js/Classes/Messages';
    import NcModal                 from '@nc/NcModal';
    import NcButton                from '@nc/NcButton';

    export default {
        components: {
            CheckboxField,
            TagsField,
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
            Translate,
            NcModal,
            NcButton
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
            },
            _fail     : {
                type   : Function,
                default: null
            }
        },

        data() {
            let cseType     = SettingsService.get('user.encryption.cse') === 1 ? 'CSEv1r1' : 'none',
                password    = Object.assign({cseType, notes: '', favorite: false, customFields: [], tags: [], hidden: false}, this.properties),
                dragService = new CustomFieldsDragService(password),
                container   = Utility.popupContainer(true);

            return {password, dragService, isFolderHidden: false, container};
        },

        computed: {
            canAddField() {
                return this.password.customFields.length < 20;
            },
            showHidden() {
                return this.properties && this.properties.hidden;
            }
        },

        methods: {
            addCustomField($event) {
                this.password.customFields.push($event);
            },
            removeCustomField(index) {
                this.password.customFields.splice(index, 1);
            },
            async submitAction() {
                let password = Utility.cloneObject(this.password);
                password = API.flattenPassword(password);
                password = API.validatePassword(password);

                if(
                    password.hidden &&
                    !this.isFolderHidden &&
                    !await Messages.confirm('PwdSaveHiddenMessage', 'PwdSaveHiddenTitle', true)
                ) {
                    return;
                }

                if(this._success) {
                    try {
                        this._success(password);
                        this.closeWindow(false);
                    } catch(e) {
                        console.error(e);
                    }
                }
            },
            dragDrop($event) {
                this.dragService.end($event);
            },
            updateFolder($event) {
                this.isFolderHidden = $event.hidden;
            },
            closeWindow(fail = true) {
                this.$destroy();
                if(this.$el.parentNode) this.$el.parentNode.removeChild(this.$el);

                if(fail && this._fail) {
                    this._fail();
                }
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

#passwords-edit-dialog {
    box-sizing : border-box;

    .modal-container {
        display        : flex;
        flex-direction : column;

        .button-vue.modal-container__close {
            z-index: 2;
        }

        @media (min-width : $width-1366-above) {
            height    : 88%;
            max-width : 76vw;
            width     : auto;
        }
    }

    .header {
        background-image : linear-gradient(40deg, #0082c9, #30b6ff);
        background-color : var(--color-primary);
        color            : var(--color-primary-text);
        display          : flex;
        flex-grow        : 0;
        flex-shrink      : 0;
        font-size        : 1.25rem;
        position         : sticky;
        top              : 0;
        z-index          : 2;

        h1 {
            flex-grow : 1;
            padding   : 1rem;
        }
    }

    .content {
        margin    : 1rem .5rem .5rem;
        flex-grow : 1;

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
        margin  : 0 .5rem .5rem;

        .advanced-options {
            display     : flex;
            align-items : flex-end;
            flex-grow   : 1;

            @media (max-width : $width-extra-small) {
                display : none;
            }
        }

        .btn-save {
            font-size    : 1.1rem;
            width        : 40%;
            margin-right : 0;

            @media (max-width : $width-extra-small) {
                width : 100%;
            }
        }
    }
}
</style>