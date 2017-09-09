Vue.component('passwords-foldout', {
    template: '#passwords-template-foldout',
    name: 'PasswordsFoldout',

    props: {
        name: {
            type: String,
            default: ''
        },
        title: {
            type: String,
            default: 'More Options'
        }
    },

    data () {
        return {
            open: false
        }
    },

    methods: {
        toggleContent: function() {
            let $element= $('.foldout-container[data-foldout='+this.name+']');
            if(OCA.Theming) {
                if($element.hasClass('open')) {
                    $element.find('.foldout-title').css('border-color', '');
                } else  {
                    $element.find('.foldout-title').css('border-color', OCA.Theming.color);
                }
            }
            $element.toggleClass('open');
        }
    }
});