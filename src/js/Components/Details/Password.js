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
            image: {
                'class': '',
                'style': {
                    'marginTop': 0
                },
            }
        }
    },

    computed: {
        date() {
            return new Date(this.password.updated * 1e3).toLocaleDateString();
        }
    },

    watch: {
        password: function (value) {
            this.image.class = '';
            this.image.style = {'marginTop': 0};
        }
    },

    methods: {
        imageMouseOver($event) {
            let $element = $($event.target),
                $parent  = $element.parent().parent(),
                margin   = $element.height() - $parent.height();

            if (margin > 0) {
                if (margin < 500) {
                    this.image.class = 's1';
                } else if (margin < 1000) {
                    this.image.class = 's5';
                } else if (margin < 2500) {
                    this.image.class = 's10';
                } else if (margin < 4000) {
                    this.image.class = 's15';
                } else {
                    this.image.class = 's20';
                }
                this.image.style = {'marginTop': '-' + margin + 'px'};
            }
        },
        imageMouseOut($event) {
            this.image.style = {'marginTop': 0};
        },
        favouriteAction($event) {
            $event.stopPropagation();
            this.password.favourite = !this.password.favourite;
            PasswordsUi.getApi().updatePassword(this.password);
        },
    }
});