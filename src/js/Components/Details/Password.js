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
        date() {
            return new Date(this.password.updated * 1e3).toLocaleDateString();
        },
    },

    methods: {
        imageMouseOver($event) {
            let $element = $($event.target),
                $parent = $element.parent(),
                margin = $element.height() - $parent.height();

            if(margin > 0) {
                $element.css('margin-top', '-' + margin + 'px');
            }
        },
        imageMouseOut($event) {
            let $element = $($event.target);

            $element.css('margin-top', 0);
        },
        favouriteAction($event) {
            $event.stopPropagation();
            this.password.favourite = !this.password.favourite;
            PasswordsUi.getApi().updatePassword(this.password);
        },
    }
});