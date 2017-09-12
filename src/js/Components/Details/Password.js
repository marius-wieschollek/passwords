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
                $parent = $element.parent().parent(),
                margin = $element.height() - $parent.height();

            $element.attr('class', '');

            if(margin > 0) {
                if(margin < 250) $element.addClass('s1');
                else if(margin < 1000) $element.addClass('s5');
                else if(margin < 2500) $element.addClass('s10');
                else if(margin < 4000) $element.addClass('s15');
                else $element.addClass('s20');
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