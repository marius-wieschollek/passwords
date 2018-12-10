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

                this._sendRequest(this.settingsUrl, {key, value}, `[data-setting="${key}"]`);
            }
        );
        $('[data-clear-cache]').click(
            (e) => {
                let $target = $(e.target),
                    cache   = $target.data('clear-cache'),
                    label   = PasswordsAdminSettings._translate(`${cache.capitalize()} Cache (0 files, 0 B)`);

                $target.parent().find('label').text(label);

                this._sendRequest(this.cacheUrl, {key:cache}, '.area.cache', 'cleared');
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
     * Send a request to the server
     *
     * @param url
     * @param data
     * @param target
     * @param success
     * @private
     */
    _sendRequest(url, data, target, success = 'saved') {
        $.post(url, data)
         .success((d) => {
             if(d.status === 'ok') {
                 this._showMessage(success, target);
             } else {
                 this._showMessage('error', target);
                 OC.Notification.show(PasswordsAdminSettings._translate(d.message));
             }
         })
         .fail(() => {this._showMessage('error', target);});
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
            $apiInput.val(data.value);
        }
    }
}

$(window).load(() => {
    let PwSettings = new PasswordsAdminSettings();
    PwSettings.initialize();
});