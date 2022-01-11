import '@scss/admin';
import AppSettingsService from '@js/Services/AppSettingsService'

class PasswordsAdminSettings {

    constructor() {
        this._timer = {success: null, error: null};
        this.api = new AppSettingsService();
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

                this.api.set(key, value)
                    .then((d) => {
                        this._showMessage('saved', `[data-setting="${key}"]`);
                    })
                    .catch((d) => {
                        this._showMessage('error', `[data-setting="${key}"]`);
                        if(d.message) OC.Notification.show(PasswordsAdminSettings._translate(d.message));
                    });
            }
        );
        $('[data-clear-cache]').click(
            (e) => {
                let $target = $(e.target),
                    cache   = $target.data('clear-cache'),
                    label   = PasswordsAdminSettings._translate(`${cache.capitalize()} Cache (0 files, 0 B)`);

                $target.parent().find('label').text(label);

                this.api.clearCache(cache)
                    .then((d) => {
                        this._showMessage('cleared', '.area.cache');
                    })
                    .catch((d) => {
                        this._showMessage('error', '.area.cache');
                        if(d.message) OC.Notification.show(PasswordsAdminSettings._translate(d.message));
                    });
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

    static _translate(text) {
        return OC.L10N.translate('passwords', text);
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
            $apiInput.attr('data-setting', data.key);
            $apiInput.val(data.value);
        }
    }
}

$(window).on('DOMContentLoaded', () => {
    let PwSettings = new PasswordsAdminSettings();
    PwSettings.initialize();
});