import '@scss/admin';
import AppSettingsService from '@js/Services/AppSettingsService';

class PasswordsAdminSettings {

    constructor() {
        this._timer = {success: null, error: null};
        this.api = new AppSettingsService();
    }

    initialize() {
        document.querySelectorAll('[data-setting]')
                .forEach((el) => {
                    el.addEventListener(
                        'change',
                        (e) => {
                            let target = e.target,
                                key    = target.dataset.setting,
                                value  = target.value;

                            if(target.getAttribute('type') === 'checkbox') {
                                value = target.checked ? 'true':'false';
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
                });


        document.querySelectorAll('[data-clear-cache]')
                .forEach((el) => {
                    el.addEventListener(
                        'click',
                        (e) => {
                            let target = e.target,
                                cache  = target.dataset.clearCache,
                                label  = PasswordsAdminSettings._translate(`${cache.capitalize()} Cache (0 files, 0 B)`);

                            target.parentNode.querySelector('label').innerText = label;

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
                });

        document.getElementById('passwords-favicon').addEventListener(
            'change',
            () => { PasswordsAdminSettings._updateApiField('favicon'); }
        );

        document.getElementById('passwords-preview').addEventListener(
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
        let element = document.querySelector('section.passwords').querySelector(target).closest('form').querySelector(`h3 .response.${type}`);
        element.classList.remove('active');
        element.classList.add('active');

        clearTimeout(this._timer[type]);
        this._timer[type] = setTimeout(
            () => {
                element.classList.remove('active');
                if(type === 'error') location.reload();
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
        let target   = document.getElementById(`passwords-${type}`),
            value    = target.value,
            option   = target.querySelector(`[value=${value}]`),
            data     = JSON.parse(option.dataset.api),
            apiInput = document.getElementById(`passwords-${type}-api`);

        if(data === null) {
            apiInput.parentNode.style.display = 'none';
        } else {
            apiInput.parentNode.style.display = '';

            apiInput.dataset.setting = data.key;
            apiInput.setAttribute('data-setting', data.key);
            apiInput.value = data.value;
        }
    }
}

window.addEventListener('DOMContentLoaded', () => {
    let PwSettings = new PasswordsAdminSettings();
    PwSettings.initialize();
});