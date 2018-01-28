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
                    label   = OC.L10N.translate('passwords', cache.capitalize() + ' Cache (0 files, 0 B)');

                $target.parent().find('label').text(label);

                PasswordsAdminSettings.clearCache(cache);
            }
        );

        $('#passwords-preview').on(
            'change',
            (e) => { PasswordsAdminSettings.updateWebsitePreviewField(); }
        );

        PasswordsAdminSettings.updateWebsitePreviewField();
    }

    static setValue(key, value) {
        $.post('/index.php/apps/passwords/admin/set', {'key': key, 'value': value})
    }

    static clearCache(key) {
        $.post('/index.php/apps/passwords/admin/cache', {'key': key})
    }

    static updateWebsitePreviewField() {
        let $target   = $('#passwords-preview'),
            value     = $target.val(),
            $option   = $target.find('[value=' + value + ']'),
            data      = $option.data('api'),
            $apiInput = $('#passwords-preview-apikey');


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