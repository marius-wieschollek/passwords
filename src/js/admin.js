import '@scss/admin';

class PasswordsAdminSettings {

    constructor() {
        this._timer = {success: null, error: null};
        this.cacheUrl = '';
        this.settingsUrl = '';
    }

    initialize() {
        this.cacheUrl = $('[data-constant="cacheUrl"]').data().value;
        this.settingsUrl = $('[data-constant="settingsUrl"]').data().value;

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
                    label   = OC.L10N.translate('passwords', `${cache.capitalize()} Cache (0 files, 0 B)`);

                $target.parent().find('label').text(label);

                this._clearCache(cache);
            }
        );

        $('#passwords-favicon').on(
            'change',
            () => { PasswordsAdminSettings._updateApiField('favicon'); }
        );

        $('#passwords-preview').on(
            'change',
            () => { PasswordsAdminSettings._updateApiField('preview'); }
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
        $.post(this.settingsUrl, {key, value})
         .success(() => {this._showMessage('saved', `[data-setting="${key}"]`);})
         .fail(() => {this._showMessage('error', `[data-setting="${key}"]`);});
    }

    /**
     * Clears a cache
     *
     * @param key
     * @private
     */
    _clearCache(key) {
        $.post(this.cacheUrl, {key})
         .success(() => {this._showMessage('cleared', `.area.cache`);})
         .fail(() => {this._showMessage('error', `.area.cache`);});
    }

    /**
     * Show save success/fail message
     *
     * @param type
     * @param target
     * @private
     */
    _showMessage(type, target) {
        let $el = $('section.passwords').find(target).parents('form').eq(0).find(`h3 .response.${type}`);
        $el.removeClass('active').addClass('active');

        clearTimeout(this._timer[type]);
        this._timer[type] = setTimeout(
            () => {
                $el.removeClass('active');
                if(type === 'error') location.reload(true);
            },
            1000);
    }

    /**
     * Show the conditional field for api keys
     *
     * @param type
     * @private
     */
    static _updateApiField(type) {
        let $target   = $(`#passwords-${type}`),
            value     = $target.val(),
            $option   = $target.find(`[value=${value}]`),
            data      = $option.data('api'),
            $apiInput = $(`#passwords-${type}-api`);


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