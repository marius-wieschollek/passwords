class PasswordsAdminSettings {

    initialize() {
        $('[data-setting]').on(
            'change',
            (e) => {
                let $target = $(e.target),
                    key     = $target.data('setting'),
                    value   = $target.val();

                this.setValue(key, value);
            }
        );
        $('[data-clear-cache]').click(
            (e) => {
                let $target = $(e.target),
                    cache   = $target.data('clear-cache'),
                    label   = OC.L10N.translate('passwords', '{cache} Cache (0 Files, 0 B)', {cache: cache.capitalize()});

                $target.parent().find('label').text(label);

                this.clearCache(cache);
            }
        );

        $('#passwords-pageshot').on(
            'change',
            (e) => { this.updatePageshotField(); }
        );

        this.updatePageshotField();
    }

    setValue(key, value) {
        $.post('/index.php/apps/passwords/admin/set', {'key': key, 'value': value})
    }

    clearCache(key) {
        $.post('/index.php/apps/passwords/admin/cache', {'key': key})
    }

    updatePageshotField() {
        let $target   = $('#passwords-pageshot'),
            value     = $target.val(),
            $option   = $target.find('[value=' + value + ']'),
            data      = $option.data('api'),
            $apiInput = $('#passwords-pageshot-apikey');


        if (data === null) {
            $apiInput.parent().hide();
        } else {
            $apiInput.parent().show();

            $apiInput.data('setting', data.key);
            $apiInput.val(data.value);
        }
    }
}

PwSettings = new PasswordsAdminSettings();

$(window).load(function () {
    PwSettings.initialize();
});