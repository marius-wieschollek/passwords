PasswordsUi.registerComponent('section.all', {
        template: '#passwords-section-all',
        data() {
            return {
                passwords: []
            }
        },

        created() {
            this.openAllPasswordsPage();
            PasswordsUi.addEventListener('data.changed', this.openAllPasswordsPage);
        },

        beforeDestroy() {
            PasswordsUi.removeEventListener('data.changed', this.openAllPasswordsPage)
        },

        methods: {
            openAllPasswordsPage: function () {
                PasswordsUi.getApi().listPasswords().then(this.updateContentList);
            },

            updateContentList: function (passwords) {
                this.passwords = passwords;
            }
        }
    }
);