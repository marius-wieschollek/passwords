import '@scss/admin';

class PasswordsAdminSettings {

    constructor() {
        this._timer = {success: null, error: null};
    }

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

                this._setValue(key, value);
            }
        );
        $('[data-clear-cache]').click(
            (e) => {
                let $target = $(e.target),
                    cache   = $target.data('clear-cache'),
                    label   = OC.L10N.translate('passwords', cache.capitalize() + ' Cache (0 files, 0 B)');

                $target.parent().find('label').text(label);

                PasswordsAdminSettings._clearCache(cache);
            }
        );

        $('#passwords-favicon').on(
            'change',
            (e) => { PasswordsAdminSettings._updateApiField('favicon'); }
        );

        $('#passwords-preview').on(
            'change',
            (e) => { PasswordsAdminSettings._updateApiField('preview'); }
        );

        PasswordsAdminSettings._updateApiField('favicon');
        PasswordsAdminSettings._updateApiField('preview');
    }

    /**
     * Update configuration value
     *
     * @param key
     * @param value
     * @private
     */
    _setValue(key, value) {
        $.post('/index.php/apps/passwords/admin/set', {'key': key, 'value': value})
         .success(() => {this._showMessage('success');})
         .fail(() => {this._showMessage('error');});
    }

    /**
     * Show save success/fail message
     *
     * @param type
     * @private
     */
    _showMessage(type) {
        let $el = $('#passwords').find('.msg.' + type);
        $el.removeClass('active').addClass('active');

        clearTimeout(this._timer[type]);
        this._timer[type] = setTimeout(
            () => { $el.removeClass('active'); },
            500
        );
    }

    /**
     * Clears a cache
     *
     * @param key
     * @private
     */
    static _clearCache(key) {
        $.post('/index.php/apps/passwords/admin/cache', {'key': key});
    }

    /**
     * Show the conditional field for api keys
     *
     * @param type
     * @private
     */
    static _updateApiField(type) {
        let $target   = $('#passwords-' + type),
            value     = $target.val(),
            $option   = $target.find('[value=' + value + ']'),
            data      = $option.data('api'),
            $apiInput = $('#passwords-' + type + '-api');


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