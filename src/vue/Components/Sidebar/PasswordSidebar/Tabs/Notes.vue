<template>
    <div v-html="notes" class="notes"></div>
</template>

<script>
    import DOMPurify from 'dompurify';
    import MessageService from "@js/Services/MessageService";
    import LoggingService from "@js/Services/LoggingService";

    export default {
        props: {
            password: {
                type: Object
            }
        },

        data() {
            return {
                notes: this.password.notes
            };
        },

        created() {
            this.processNotes();
        },

        methods: {
            async processNotes() {
                try {
                    let marked = await import(/* webpackChunkName: "marked" */ 'marked');
                    marked.setOptions({breaks: true});
                    this.notes = DOMPurify.sanitize(marked.marked.parse(this.password.notes));
                } catch(e) {
                    LoggingService.error(e);
                    MessageService.alert(['Unable to load {module}', {module: 'Marked'}], 'Network error');
                }
            }
        },

        watch: {
            password(value) {
                this.processNotes();
            }
        }
    };
</script>

<style lang="scss">
#app-sidebar-vue .notes {

    blockquote {
        font-family : var(--pw-mono-font-face);
        margin      : 5px 0;
        padding     : 10px 0 10px 15px;
        border-left : 2px solid $color-grey-dark;
    }

    pre {
        font-family      : var(--pw-mono-font-face);
        background-color : var(--color-background-dark);
        color            : var(--color-text-lighter);
        padding          : 1rem;
        border-radius    : var(--border-radius);
        white-space      : pre-wrap;
        word-wrap        : break-word;
    }

    h1, h2, h3, h4, h5, h6 {
        font-size   : 1.75rem;
        font-weight : 600;
        display     : block;
        padding     : 0;
        margin      : 0.25rem 0 0.5rem;
        line-height : initial;
    }

    h2 { font-size : 1.6rem; }

    h3 { font-size : 1.4rem; }

    h4 { font-size : 1.2rem; }

    h5 { font-size : 1.1rem; }

    h6 { font-size : 0.9rem; }

    em { font-style : italic; }

    ul {
        list-style   : disc;
        padding-left : 15px;

        li {
            list-style-type : inherit;
        }
    }

    ol {
        list-style   : decimal;
        padding-left : 15px;

        li {
            list-style-type : inherit;
        }
    }

    a {
        text-decoration : underline;
    }

    p {
        margin-bottom : 1em;

        > code {
            font-family      : var(--pw-mono-font-face);
            padding          : .25rem .5rem;
            background-color : var(--color-background-dark);
            color            : var(--color-text-lighter);
            border-radius    : var(--border-radius);
        }
    }

    table {
        border-collapse : collapse;
        width           : 100%;

        th {
            text-align  : center;
            font-weight : bold;
        }

        td,
        th {
            border  : 1px solid var(--color-border-dark);
            padding : 0.25rem;
        }
    }
}
</style>