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
    <div class="password-form-notes-wrapper">
        <translate tag="h3" class="notes-label" say="Notes" icon="sticky-note"/>
        <textarea ref="textarea" id="password-notes" name="notes" :maxlength="maxlength"></textarea>
    </div>
</template>

<script>
    import SimpleMDE from 'simplemde';
    import Messages from '@js/Classes/Messages';
    import Localisation from '@js/Classes/Localisation';
    import Translate from '@vc/Translate';
    import Icon from '@vc/Icon';
    import AbstractField from '@vue/Dialog/CreatePassword/AbstractField';
    import Utility from "@js/Classes/Utility";

    const MaxNotesLength = 4096;

    export default {
        extends   : AbstractField,
        components: {Icon, Translate},

        data() {
            return {
                maxlength: MaxNotesLength,
                simplemde: null
            };
        },

        computed: {
            isOverMaxlength() {
                return this.model.length >= MaxNotesLength;
            }
        },

        mounted() {
            this.loadSimpleMde();
        },

        methods: {
            /**
             *
             * @param {HTMLDivElement} el
             */
            updateStatusBar(el) {
                if(!this.isOverMaxlength) {
                    el.classList.remove('warning');
                    el.innerText = `${this.model.length}/${MaxNotesLength}`;
                } else {
                    el.classList.add('warning');
                    el.innerText = Localisation.translate('You have reached the maximum length of 4096 characters');
                }
            },
            async loadSimpleMde() {
                try {
                    this.simplemde = new SimpleMDE(
                        {
                            element                : this.$refs.textarea,
                            autoDownloadFontAwesome: false,
                            spellChecker           : false,
                            placeholder            : Localisation.translate('Take some notes'),
                            forceSync              : true,
                            initialValue           : this.model,
                            toolbar                : [
                                'bold', 'italic', 'heading', 'quote', 'code',
                                '|', 'unordered-list', 'ordered-list', 'table', 'horizontal-rule',
                                '|', 'link',
                                '|', 'preview', 'undo', 'redo', '|',
                                {
                                    name     : "help",
                                    action   : Utility.generateUrl('/apps/passwords/#/help/Passwords%2FMarkdown-Notes'),
                                    className: "fa fa-question-circle",
                                    title    : "Markdown Guide",
                                    default  : true
                                }
                            ],
                            blockStyles            : {italic: '_'},
                            status                 : [
                                {
                                    defaultValue: (el) => { this.updateStatusBar(el); },
                                    onUpdate    : (el) => { this.updateStatusBar(el); }
                                }
                            ]
                        }
                    );
                    this.simplemde.codemirror.on('change', () => {
                        let value = this.simplemde.value();
                        if(value.length > MaxNotesLength) {
                            value = value.substring(0, MaxNotesLength);
                            this.simplemde.value(value);
                        }
                        this.model = value;
                    });
                } catch(e) {
                    console.error(e);
                    Messages.alert(['Unable to load {module}', {module: 'SimpleMde'}], 'Network error');
                }
            }
        },
        watch  : {
            value(value) {
                if(this.model !== value) {
                    if(this.simplemde) this.simplemde.value(value);
                }
            }
        }
    };
</script>

<style lang="scss">
.password-form-notes-wrapper {
    position   : relative;
    margin-top : auto;

    h3.notes-label {
        position    : absolute;
        line-height : 2rem;
        font-weight : bold;
    }

    .editor-toolbar {
        border     : none;
        padding    : 0;
        text-align : right;

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

        ul li {
            list-style-type : disc;
        }

        ol li {
            list-style-type : decimal;
        }
    }

    .warning {
        margin      : 0 0 4px;
        width       : 100%;
        text-align  : left;
        font-weight : bold;
    }
}
</style>