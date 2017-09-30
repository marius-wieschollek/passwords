<template id="passwords-translate">
    <span>{{ getText }}</span>
</template>

<script>
    export default {
        template: '#passwords-translate',

        props: {
            say      : {
                type     : String,
                'default': ''
            },
            variables: {
                type     : Object,
                'default': () => {}
            }
        },

        computed: {
            getText() {
                if (OC !== undefined) {
                    return this.ocTranslate(this.say, this.variables)
                }
            }
        },

        methods: {
            ocTranslate(text, variables = {}) {
                return OC.L10N.translate('passwords', text, variables);
            }
        }
    };
</script>