PasswordsUi.registerComponent('dialog.password.create', {
    template: '#passwords-partial-password-create',
    data() {
        return {
            showPassword: false
        }
    },
    mounted() {
        let SimpleMDENotices = new SimpleMDE(
            {
                element                : document.getElementById("password-notes"),
                hideIcons              : ['fullscreen', 'side-by-side'],
                autoDownloadFontAwesome: false,
                spellChecker           : false,
                placeholder            : 'Make some notes',
                status                 : false
            });
        if (OCA.Theming) {
            $('#passwords-create-new .section-title, #passwords-create-new .notes label')
                .css('border-color', OCA.Theming.color);
        }
    },

    methods: {
        closeWindow             : function () {
            let $container = $('#app-popup');
            this.$destroy();
            $container.find('div').remove();
            $container.html('<div></div>');
        },
        togglePasswordVisibility: function () {
            let $element = $('.password-field .icons i.fa:nth-child(1)');
            if ($element.hasClass('fa-eye')) {
                $element.removeClass('fa-eye').addClass('fa-eye-slash');
                $element.parents('.password-field').find('input').attr('type', 'text');
            } else {
                $element.removeClass('fa-eye-slash').addClass('fa-eye');
                $element.parents('.password-field').find('input').attr('type', 'password');
            }
            this.showPassword = !this.showPassword;
        },
        generateRandomPassword  : async function () {
            let $element = $('.password-field .icons  i.fa:nth-child(2)');
            $element.addClass('fa-spin');
            let password = await PasswordsUi.getApi().generatePassword();
            $element.parents('.password-field').find('input').val(password.password);
            $element.removeClass('fa-spin');
            if (!this.showPassword) {
                this.togglePasswordVisibility()
            }
        },
        submitCreatePassword    : async function ($event) {
            let $element = $($event.target);
            let $data = $element.serializeArray();
            let password = {};

            for(let i=0; i<$data.length; i++) {
                let entry = $data[i];
                password[entry.name] = entry.value;
            }

            try {
                let response = await PasswordsUi.getApi().createPassword(password);
                PasswordsUi.fireEvent('password.created', response);
                PasswordsUi.fireEvent('data.changed');
                PasswordsUi.notification('Password created');
                this.closeWindow();
            } catch(e) {
                UserInterface.alert(e.message, 'Creating Password Failed');
            }
        }
    }
});