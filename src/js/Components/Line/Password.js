Vue.component('passwords-line-password', {
    template: '#passwords-template-password-line',
    name    : 'PasswordsFoldout',

    props: {
        password: {
            type: Object
        }
    },

    data() {
        return {
            clickTimeout: null
        }
    },

    computed: {
        faviconStyle() {
            return {
                backgroundImage: 'url(' + this.password.icon + ')'
            }
        },
        date() {
            return new Date(this.password.updated * 1e3).toLocaleDateString();
        },
        securityCheck() {
            console.log(this.password.secure);
            switch(this.password.secure) {
                case 0: return 'ok';
                case 1: return 'warn';
                case 2: return 'fail';
            }
        }
    },

    methods: {
        singleClickAction($event) {
            if ($event.detail !== 1) return;
            copyToClipboard(this.password.password);

            if (this.clickTimeout) clearTimeout(this.clickTimeout);
            this.clickTimeout = setTimeout(function () { PasswordsUi.notification('Password was copied to clipboard') }, 300);
        },
        doubleClickAction() {
            if (this.clickTimeout) clearTimeout(this.clickTimeout);

            copyToClipboard(this.password.login);
            PasswordsUi.notification('Username was copied to clipboard');
        },
        favouriteAction($event) {
            $event.stopPropagation();
            this.password.favourite = !this.password.favourite;
            PasswordsUi.getApi().updatePassword(this.password);
        },
        toggleMenu($event) {
            $event.stopPropagation();
            $($event.target).parents('.row.password').find('.passwordActionsMenu').toggleClass('open');
        },
        copyUrlAction() {
            copyToClipboard(this.password.url);
            PasswordsUi.notification('Url was copied to clipboard')
        },
        deleteAction() {
            PasswordsUi.confirm('Do you want to delete the password', 'Delete password')
                .then(() => {
                    PasswordsUi.getApi().deletePassword(this.password.id)
                        .then(() => {
                            PasswordsUi.notification('Password was deleted');
                        }).catch(() => {
                            PasswordsUi.notification('Deleting password failed');
                        });
                })
        }
    }
});