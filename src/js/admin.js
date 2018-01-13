import '@scss/admin';

class PasswordsAdminSettings {

    initialize() {
        $('[data-setting]').on(
            'change',
            (e) => {
                let $target = $(e.target),
                    key     = $target.data('setting'),
                    value   = $target.val();

                if($target.attr('type') === 'checkbox') {
                    value = $target[0].checked ? 'true':'false';
                }

                PasswordsAdminSettings.setValue(key, value);
            }
        );
        $('[data-clear-cache]').click(
            (e) => {
                let $target = $(e.target),
                    cache   = $target.data('clear-cache'),
                    label   = OC.L10N.translate('passwords', '{cache} Cache (0 Files, 0 B)', {cache: cache.capitalize()});

                $target.parent().find('label').text(label);

                PasswordsAdminSettings.clearCache(cache);
            }
        );

        $('#passwords-pageshot').on(
            'change',
            (e) => { PasswordsAdminSettings.updatePageshotField(); }
        );

        PasswordsAdminSettings.updatePageshotField();
    }

    static setValue(key, value) {
        $.post('/index.php/apps/passwords/admin/set', {'key': key, 'value': value})
    }

    static clearCache(key) {
        $.post('/index.php/apps/passwords/admin/cache', {'key': key})
    }

    static updatePageshotField() {
        let $target   = $('#passwords-pageshot'),
            value     = $target.val(),
            $option   = $target.find('[value=' + value + ']'),
            data      = $option.data('api'),
            $apiInput = $('#passwords-pageshot-apikey');


        if(data === null) {
            $apiInput.parent().hide();
        } else {
            $apiInput.parent().show();

            $apiInput.data('setting', data.key);
            $apiInput.val(data.value);
        }
    }
}

$(window).load(() => {
    let PwSettings = new PasswordsAdminSettings();
    PwSettings.initialize();
});