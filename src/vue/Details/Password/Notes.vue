<template>
    <div v-html="notes" class="notes"></div>
</template>

<script>
    import Messages from '@js/Classes/Messages';

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
                    this.notes = marked(this.password.notes, {breaks: true});
                } catch(e) {
                    console.error(e);
                    Messages.alert(['Unable to load {module}', {module: 'Marked'}], 'Network error');
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
    .item-details .notes {
        blockquote {
            font-family : monospace;
            margin      : 5px 0;
            padding     : 10px 0 10px 15px;
            border-left : 2px solid $color-grey-dark;
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
        }
        ol {
            list-style   : decimal;
            padding-left : 15px;
        }
        a {
            text-decoration : underline;
        }
        p {
            margin-bottom : 1em;
        }
    }
</style>