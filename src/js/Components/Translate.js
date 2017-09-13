Vue.component('passwords-translate', {
    template: '#passwords-translate',

    props: {
        say: {
            type: String,
            default: ''
        },
        arguments: {
            type: Object,
            default: {}
        }
    },

    computed : {
        getText() {
            if(OC !== undefined) {
                return this.ocTranslate(this.say, this.arguments)
            }
        }
    },

    methods: {
        ocTranslate(text, arguments = {}) {
            OC.L10N.translate('passwords', text, arguments);
        }
    }
});