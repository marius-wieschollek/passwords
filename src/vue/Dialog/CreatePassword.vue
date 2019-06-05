<template>
    <div class="background" id="passwords-create-new">
        <div class="window">
            <div class="title">
                <translate :say="title"/>
                <i class="fa fa-times close" @click="closeWindow()"></i>
            </div>
            <form class="content" v-on:submit.prevent="submitAction()">
                <div class="form left">
                    <translate tag="div" class="section-title" say="Properties"/>
                    <div class="form-grid">
                        <translate tag="label" for="password-username" say="Username"/>
                        <input id="password-username"
                               type="text"
                               name="username"
                               maxlength="64"
                               v-model="password.username"
                               autocomplete="off">
                        <translate tag="label" for="password-password" say="Password"/>
                        <div class="password-field">
                            <div class="icons">
                                <translate tag="i"
                                           class="fa"
                                           :class="{ 'fa-eye': showPassword, 'fa-eye-slash': !showPassword }"
                                           @click="togglePasswordVisibility()"
                                           title="Toggle visibility"/>
                                <translate tag="i"
                                           class="fa fa-refresh"
                                           :class="{ 'fa-spin': showLoader }"
                                           @click="generateRandomPassword()"
                                           title="Generate password"/>
                            </div>
                            <input id="password-password"
                                   :type="showPassword ? 'text':'password'"
                                   name="password"
                                   pattern=".{0,256}"
                                   autocomplete="new-password"
                                   v-model="password.password"
                                   required
                                   readonly>
                        </div>
                        <div class="settings" :class="{active: generator.active}">
                            <input id="password-password-numbers"
                                   type="checkbox"
                                   v-model="generator.numbers"
                                   :disabled="!generator.active"/>
                            <translate tag="label" for="password-password-numbers" say="Numbers"/>
                            <input id="password-password-special"
                                   type="checkbox"
                                   v-model="generator.special"
                                   :disabled="!generator.active"/>
                            <translate tag="label" for="password-password-special" say="Special Characters"/>
                        </div>
                        <translate tag="label" for="password-label" say="Name"/>
                        <input id="password-label" type="text" name="label" maxlength="64" v-model="password.label">
                        <translate tag="label" for="password-url" say="Website"/>
                        <input id="password-url" type="url" name="url" maxlength="2048" v-model="password.url">
                        <!-- <passwords-tags></passwords-tags> -->
                    </div>
                </div>
                <div class="form right">
                    <foldout title="Notes" :initially-open="notesOpen">
                        <div class="notes-container">
                            <translate tag="div"
                                       class="warning"
                                       say="You have reached the maximum length of 4096 characters"
                                       v-if="password.notes.length > 4095"/>
                            <textarea id="password-notes" name="notes" maxlength="4096"></textarea>
                        </div>
                    </foldout>
                    <foldout title="Custom Fields">
                        <custom-fields :fields="password.customFields" @updated="updateCustomFields"/>
                    </foldout>
                    <foldout title="More Options">
                        <div class="form-grid">
                            <translate tag="label" for="password-favorite" say="Favorite"/>
                            <input id="password-favorite" name="favorite" type="checkbox" v-model="password.favorite">
                            <translate tag="label" for="password-cse" say="Encryption"/>
                            <select id="password-cse"
                                    name="cseType"
                                    title="Choose the encryption type for this password"
                                    v-model.number="password.cseType"
                                    :disabled="!hasEncryption">
                                <translate tag="option" value="none" say="On the server"/>
                                <translate tag="option" value="CSEv1r1" say="Libsodium"/>
                            </select>
                        </div>
                    </foldout>
                </div>
                <div class="controls">
                    <translate tag="input" type="submit" localized-value="Save"/>
                </div>
            </form>
        </div>
    </div>
</template>

<script>
    import API from "@js/Helper/api";
    import Foldout from '@vc/Foldout';
    import Translate from '@vc/Translate';
    import Utility from '@js/Classes/Utility';
    import Messages from "@js/Classes/Messages";
    import Localisation from '@js/Classes/Localisation';
    import EnhancedApi from '@js/ApiClient/EnhancedApi';
    import SettingsManager from '@js/Manager/SettingsManager';
    import CustomFields from '@vue/Dialog/CreatePassword/CustomFields';

    export default {
        components: {
            Foldout,
            Translate,
            CustomFields
        },

        props: {
            title     : {
                type     : String,
                'default': 'Create password',
            },
            properties: {
                type: Object
            },
            _success  : {
                type: Function
            }
        },

        data() {
            let cseType  = SettingsManager.get('user.encryption.cse') === 1 ? 'CSEv1r1':'none',
                password = Object.assign({cseType, notes: '', customFields: []}, this.properties);

            return {
                notesOpen    : window.innerWidth > 641,
                showPassword : false,
                showLoader   : false,
                simplemde    : null,
                generator    : {numbers: undefined, special: undefined, active: false},
                hasEncryption: API.hasEncryption,
                password
            };
        },

        mounted() {
            this.loadSimpleMde();
            document.getElementById('password-username').focus();
            setTimeout(
                () => {document.getElementById('password-password').removeAttribute('readonly');},
                250
            );
        },

        methods: {
            closeWindow() {
                this.$destroy();
                let container = document.getElementById('app-popup'),
                    div       = document.createElement('div');
                container.replaceChild(div, container.childNodes[0]);
            },
            togglePasswordVisibility() {
                this.showPassword = !this.showPassword;
            },
            generateRandomPassword() {
                if(this.showLoader) return;
                this.showLoader = true;
                let numbers = undefined, special = undefined;

                if(this.generator.active) {
                    numbers = this.generator.numbers;
                    special = this.generator.special;
                }

                API.generatePassword(undefined, numbers, special)
                    .then((d) => {
                        this.password.password = d.password;
                        if(this.generator.active === false) {
                            this.generator = {numbers: d.numbers, special: d.special, active: true};
                        }
                        this.showPassword = true;
                        this.showLoader = false;
                    })
                    .catch(() => {
                        this.showLoader = false;
                    });
            },
            updateCustomFields($event) {
                this.password.customFields = Utility.arrayValues($event);
            },
            submitAction() {
                let password = Utility.cloneObject(this.password);
                password = EnhancedApi.flattenPassword(password);
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
            async loadSimpleMde() {
                try {
                    let SimpleMDE = await import(/* webpackChunkName: "simplemde" */ 'simplemde');

                    this.simplemde = new SimpleMDE(
                        {
                            element                : document.getElementById('password-notes'),
                            hideIcons              : ['fullscreen', 'side-by-side', 'image'],
                            autoDownloadFontAwesome: false,
                            spellChecker           : false,
                            placeholder            : Localisation.translate('Take some notes'),
                            forceSync              : true,
                            initialValue           : this.password.notes,
                            status                 : [
                                {
                                    defaultValue: (el) => {el.innerHTML = this.password.notes.length + '/4096';},
                                    onUpdate    : (el) => {el.innerHTML = this.password.notes.length + '/4096';}
                                }
                            ]
                        }
                    );
                    this.simplemde.codemirror.on('change', () => {
                        let value = this.simplemde.value();
                        if(value.length > 4096) {
                            value = value.substring(0, 4096);
                            this.simplemde.value(value);
                        }
                        this.password.notes = value;
                    });
                } catch(e) {
                    console.error(e);
                    Messages.alert(['Unable to load {module}', {module: 'SimpleMde'}], 'Network error');
                }
            }
        },

        watch: {
            password(password) {
                if(typeof password.customFields === "string") password.customFields = JSON.parse(password.customFields);
                if(password.customFields === null) password.customFields = [];
                if(this.simplemde) this.simplemde.value(password.notes);
            },
            'generator.numbers'(value, oldValue) {
                if(oldValue !== undefined) this.generateRandomPassword();
            },
            'generator.special'(value, oldValue) {
                if(oldValue !== undefined) this.generateRandomPassword();
            }
        }
    };
</script>

<style lang="scss">
    @import "~simplemde/dist/simplemde.min.css";

    #app-popup {
        .background {
            position         : fixed;
            top              : 0;
            left             : 0;
            width            : 100%;
            height           : 100%;
            background-color : rgba(0, 0, 0, 0.7);
            z-index          : 3001;
            display          : flex;
            justify-content  : center;
            align-items      : center;

            .window {
                z-index               : 9999;
                overflow              : hidden;
                background-color      : var(--color-main-background);
                border-radius         : var(--border-radius-large);
                box-sizing            : border-box;
                display               : grid;
                grid-template-columns : 100%;
                grid-template-areas   : "title" "content";
                grid-template-rows    : 3.25rem auto;
                justify-items         : stretch;
                align-items           : stretch;

                .title {
                    grid-area        : title;
                    padding          : 1rem;
                    font-size        : 1.25rem;
                    color            : var(--color-primary-text);
                    background-color : var(--color-primary);
                    position         : sticky;
                    top              : 0;

                    .close {
                        float  : right;
                        cursor : pointer;
                    }
                }

                .content {
                    grid-area : content;
                    overflow  : auto;
                }

                @media (max-width : $width-medium) {
                    border-radius : 0;
                    top           : 0;
                    left          : 0;
                    bottom        : 0;
                    right         : 0;
                    width         : 100%;
                    height        : 100%;
                }
            }
        }

        #passwords-create-new {
            .window {
                height : 88%;

                @media (max-width : $width-medium) {
                    height : 100%;
                }
            }

            .content {
                display               : grid;
                grid-template-columns : 1fr 1fr;
                grid-template-rows    : 9fr 1fr;
                grid-template-areas   : "left right" "controls right";
                grid-column-gap       : 15px;
                padding               : 15px;

                .form {
                    grid-area : left;

                    .form-grid {
                        display               : grid;
                        grid-template-columns : auto 3fr;
                        grid-template-rows    : 1fr;
                        grid-row-gap          : 5px;
                        justify-items         : left;
                        align-items           : end;

                        .tags-container,
                        .foldout-container {
                            grid-column  : 1 / span 2;
                            justify-self : stretch;
                        }

                        label {
                            padding : 0 0.9rem 5px 0;
                            cursor  : pointer;
                        }
                    }

                    .section-title {
                        font-size     : 1.1rem;
                        padding       : 0 0 0.25rem 0;
                        border-bottom : 1px solid var(--color-primary);
                    }

                    .password-field {
                        display   : block;
                        width     : 100%;
                        max-width : 275px;
                        position  : relative;

                        input {
                            max-width     : initial;
                            padding-right : 45px;
                            font-family   : var(--pw-mono-font-face);
                        }

                        .icons {
                            position    : absolute;
                            top         : 0;
                            right       : 3px;
                            bottom      : 0;
                            display     : flex;
                            align-items : center;

                            i.fa {
                                font-size : 1rem;
                                cursor    : pointer;
                                margin    : 3px;
                            }
                        }
                    }

                    label {
                        display   : block;
                        font-size : 0.9rem;
                    }

                    input[type=url],
                    input[type=text],
                    input[type=password] {
                        cursor    : text;
                        width     : 100%;
                        max-width : 275px;
                    }

                    input[type=checkbox] {
                        cursor : pointer;
                    }

                    select {
                        width     : 100%;
                        max-width : 275px;
                    }

                    textarea {
                        opacity : 0;
                    }

                    .settings {
                        grid-column-start : 2;
                        grid-column-end   : 3;
                        line-height       : 30px;
                        display           : flex;
                        overflow          : hidden;
                        max-height        : 0;
                        transition        : max-height 0.25s ease-in-out;

                        &.active {
                            max-height : 60px;
                        }

                        input {
                            margin : 0;
                        }

                        label {
                            padding : 0 10px 0 5px;
                        }
                    }

                    &.right {
                        grid-area  : right;
                        overflow-y : auto;

                        .notes-container {
                            padding : 0.25em 0;
                            width   : 525px;

                            .editor-toolbar {
                                border  : none;
                                padding : 0;

                                a {
                                    background : var(--color-main-background);
                                    border     : 1px solid transparent;

                                    &:hover,
                                    &:active,
                                    &:focus {
                                        background   : var(--color-main-background);
                                        border-color : var(--color-border);
                                        cursor       : pointer;
                                    }

                                    &:before {
                                        color : var(--color-main-text);
                                    }
                                }

                                .separator {
                                    border-left  : none;
                                    border-right : 1px solid var(--color-border);
                                }
                            }

                            .CodeMirror {
                                background    : var(--color-main-background);
                                color         : var(--color-main-text);
                                border-color  : var(--color-border);
                                border-radius : var(--border-radius);
                            }

                            .CodeMirror-code {
                                width   : auto;
                                border  : none;
                                padding : 0;
                                margin  : 0;
                            }

                            .CodeMirror-scroll {
                                overflow   : auto !important;
                                min-height : 300px;
                                max-height : 300px;
                            }

                            .CodeMirror-cursor,
                            .CodeMirror-cursors {
                                border      : none;
                                border-left : 1px solid var(--color-main-text);
                            }

                            .CodeMirror-selectedtext,
                            .CodeMirror-selectedtext::selection {
                                color      : var(--color-main-background);
                                background : var(--color-main-text);
                            }

                            .editor-preview.editor-preview-active {
                                background : var(--color-background-dark);

                                p {
                                    margin-bottom : 1em;
                                }
                            }

                            .warning {
                                margin : 0 0 4px;
                            }

                            @media (max-width : $width-medium) {
                                width   : 100%;
                                max-width   : 525px;
                            }
                        }

                        .foldout-container {
                            transition : padding-bottom 0.1s ease-in-out;

                            &.open {
                                padding-bottom : 1.25rem;
                            }

                            &.first-open {
                                transition : none;
                            }
                        }

                    }
                }

                .controls {
                    grid-area  : controls;
                    align-self : end;

                    input {
                        width     : 100%;
                        font-size : 1.1rem;
                    }
                }

                @media (max-width : $width-extra-small) {
                    display : block;

                    .form.left {
                        padding-bottom : 1.25rem;
                    }
                }
            }
        }
    }
</style>