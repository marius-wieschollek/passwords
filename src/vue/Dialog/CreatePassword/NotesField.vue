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
    <div class="password-form-notes-wrapper">
        <translate tag="h3" class="notes-label" say="Notes" icon="sticky-note"/>
        <translate ref="label" tag="h3" class="notes-label" say="Notes" icon="sticky-note"/>
        <div ref="textarea" id="password-notes"></div>
        <div class="notes-status" :class="{warning:isOverMaxlength}">{{ statusText }}</div>
    </div>
</template>

<script>
    import Editor from '@toast-ui/editor';
    import Icon from '@vc/Icon';
    import Translate from '@vc/Translate';
    import AbstractField from '@vue/Dialog/CreatePassword/AbstractField';
    import MessageService from "@js/Services/MessageService";
    import UtilityService from "@js/Services/UtilityService";
    import LocalisationService from "@js/Services/LocalisationService";

    const MaxNotesLength = 4096;

    export default {
        extends   : AbstractField,
        components: {Icon, Translate},

        data() {
            return {
                maxlength: MaxNotesLength,
                editor   : null
            };
        },

        computed: {
            statusText() {
                if(!this.isOverMaxlength) {
                    return `${this.model.length}/${MaxNotesLength}`;
                } else {
                    return LocalisationService.translate('You have reached the maximum length of 4096 characters');
                }
            },
            isOverMaxlength() {
                return this.model.length >= MaxNotesLength;
            }
        },

        mounted() {
            this.loadEditor();
        },

        methods: {
            getHelpButton() {
                const button = document.createElement('button');
                button.style.backgroundImage = 'none';
                button.style.margin = '0';
                button.innerHTML = ' ';
                button.className = 'toastui-editor-toolbar-icons last fa fa-question-circle';
                button.addEventListener('click', () => {
                    let url = UtilityService.generateUrl('/apps/passwords/#/help/Passwords%2FMarkdown-Notes');
                    UtilityService.openLink(url);
                });
                return button;
            },
            /**
             *
             */
            async loadEditor() {
                try {

                    this.editor = new Editor(
                        {
                            el             : this.$refs.textarea,
                            autofocus      : false,
                            height         : '100%',
                            initialValue   : this.model,
                            previewStyle   : window.innerWidth <= 640 ? 'tab':'vertical',
                            usageStatistics: false,
                            language       : LocalisationService.locale,
                            placeholder    : LocalisationService.translate('Take some notes'),
                            hideModeSwitch : true,
                            toolbarItems   : [
                                [
                                    {
                                        el: this.$refs.label.$el
                                    },
                                    'heading', 'bold', 'italic', 'strike'
                                ],
                                ['hr', 'quote'],
                                ['ul', 'ol', 'indent', 'outdent'],
                                ['table', 'link'],
                                ['code', 'codeblock'],
                                [
                                    {
                                        name     : 'undo',
                                        command  : 'undo',
                                        className: 'toastui-editor-toolbar-icons fa fa-undo',
                                        tooltip  : LocalisationService.translate('Undo')
                                    },
                                    {
                                        name     : 'redo',
                                        command  : 'redo',
                                        className: 'toastui-editor-toolbar-icons fa fa-repeat',
                                        tooltip  : LocalisationService.translate('Redo')
                                    },
                                    {
                                        el     : this.getHelpButton(),
                                        command: '',
                                        tooltip: LocalisationService.translate('Open Markdown Guide')
                                    }
                                ]
                            ],
                            events         : {
                                change: () => {
                                    let value = this.editor.getMarkdown();
                                    if(value.length > MaxNotesLength) {
                                        value = value.substring(0, MaxNotesLength);
                                        this.editor.setMarkdown(value, true);
                                        this.editor.blur();
                                    }
                                    this.model = value;
                                }
                            }
                        }
                    );
                } catch(e) {
                    console.error(e);
                    MessageService.alert(['Unable to load {module}', {module: 'ToastUI Editor'}], 'Network error');
                }
            }
        },
        watch  : {
            value(value) {
                if(this.model !== value) {
                    if(this.editor) this.editor.setMarkdown(value, true);
                }
            }
        }
    };
</script>

<style lang="scss">
@import "@toast-ui/editor/dist/toastui-editor.css";

.password-form-notes-wrapper {
    position    : relative;
    margin-top  : auto;
    padding-top : 6rem;
    height      : 100%;

    > h3.notes-label {
        display     : none;
        white-space : nowrap;
    }

    #password-notes {
        .toastui-editor-defaultUI {
            font-family  : inherit;
            border-color : var(--color-border);

            .toastui-editor-toolbar {
                .toastui-editor-popup-add-heading h1 {
                    font-weight : bold;
                }

                .toastui-editor-defaultUI-toolbar {
                    background-color : var(--color-background-hover);
                    border-color     : var(--color-border);

                    .toastui-editor-toolbar-group {
                        .toastui-editor-toolbar-icons {
                            background-color      : transparent;
                            border-color          : var(--color-background-hover);
                            background-position-y : 4px;
                            min-height            : 34px;

                            &:not(:disabled).active {
                                background-color      : var(--color-primary-element-hover);
                                background-position-y : -48px;
                            }

                            &:not(:disabled):hover {
                                background-position-y : -23px;
                                background-color      : var(--color-main-background);
                            }

                            &.fa {
                                background-image : none;
                                color            : var(--color-text-maxcontrast);

                                &:hover {
                                    padding-bottom : 2px;
                                    color          : var(--color-primary);
                                }
                            }
                        }

                        .toastui-editor-toolbar-item-wrapper {
                            h3.notes-label {
                                margin      : 0 7.5rem 0 -1rem;
                                line-height : 2rem;
                                white-space : nowrap;

                                @media all and (max-width : 1390px) {
                                    margin : 0 .5rem 0 -1rem;
                                }
                            }
                        }
                    }
                }
            }

            .toastui-editor-main {
                min-height : 100px;

                .toastui-editor-main-container {
                    min-height : 100px;

                    .ProseMirror {
                        box-shadow : none;
                    }
                }

                .toastui-editor-md-splitter {
                    background-color : var(--color-border);
                }
            }

            div[contenteditable="true"] {
                width  : auto;
                border : none;
                height : 100%;
            }

            .toastui-editor.md-mode,
            .toastui-editor.md-mode div,
            .toastui-editor.md-mode span {
                cursor : text;

                .placeholder {
                    color : var(--color-placeholder-dark);
                }

                &.toastui-editor-md-code,
                &.toastui-editor-md-code-block,
                &.toastui-editor-md-code-block-line-background {
                    background-color : var(--color-background-dark);
                    color            : var(--color-text-maxcontrast);

                    &.toastui-editor-md-marked-text {
                        color : var(--color-text-maxcontrast);
                    }
                }
            }

            .toastui-editor-contents {
                font-family : inherit;
                color       : var(--color-primary-text);

                p {
                    margin-bottom : 1em;
                    color         : var(--color-primary-text);
                }

                ol li {
                    list-style-type : decimal;
                    color           : var(--color-primary-text);
                }

                h1, h2 {
                    border-bottom : none;
                    color         : var(--color-primary-text);
                }

                pre,
                code {
                    font-family      : var(--pw-mono-font-face);
                    background-color : var(--color-background-dark);
                    color            : var(--color-text-maxcontrast);
                    border-radius    : var(--border-radius);
                    word-wrap        : break-word;
                }
            }
        }
    }

    .notes-status {
        position : absolute;
        right    : 0.5rem;
        bottom   : 0.25rem;
        color    : var(--color-primary-text-dark);

        &.warning {
            color       : var(--color-primary-text);
            margin      : 0 0 4px;
            text-align  : left;
            font-weight : bold;
        }
    }

    @media all and (max-width : $width-1024) {
        h3.notes-label {
            font-weight : bold;
            line-height : var(--default-line-height);
            display     : block;
        }

        .toastui-editor-toolbar-item-wrapper {
            h3.notes-label {
                display : none;
            }
        }
    }

    @media all and (max-height : $height-1024) {
        padding-top : .5rem;
    }
}

body.theme--dark {
    .password-form-notes-wrapper {
        #password-notes {
            .toastui-editor-defaultUI {
                .toastui-editor-toolbar {
                    .toastui-editor-defaultUI-toolbar {
                        .toastui-editor-toolbar-group {
                            .toastui-editor-toolbar-icons {
                                background-position-y : -48px;
                            }
                        }
                    }
                }
            }
        }
    }
}
</style>