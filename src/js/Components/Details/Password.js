Vue.component('passwords-details-password', {
    template: '#passwords-template-password-details',
    name    : 'PasswordDetails',

    props: {
        password: {
            type: Object
        }
    },

    data() {
        return {
        }
    },

    computed: {
    },

    methods: {
        imageMouseOver($event) {
            let $element = $($event.target),
                $parent = $element.parent(),
                height = $element.height() - $parent.height();
            console.log(height, $element.height(), $parent.height());

            $element.css('margin-top', '-' + height + 'px');
        },
        imageMouseOut($event) {
            let $element = $($event.target);
            console.log('test');

            $element.css('margin-top', 0);
        }
    }
});