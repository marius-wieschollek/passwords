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

        var mapping = document.getElementById('domain-mapping-custom').childNodes[0]
        mapping = JSON.parse(mapping.data);
        mapping.data.forEach(element => {
            this._addLineToTable(element.join(' '));
        });
        this._addLineToTable();

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
            $apiInput.attr('data-setting', data.key);
            $apiInput.val(data.value);
        }
    }

    /**
     * Save domain mapping settings back to settings
     *
     * @private
     */
    _onChangeCustomDomainMapping() {
        var result = [];
        for (var i = 0; i < $('.domain-mapping-custom').length; i++) { 
           var element = $('.domain-mapping-custom').eq(i);

           if(element.val() === "") {
               element.remove();
           } else {
               var mapping = element.val().split(' ').filter(function (el) {
                    return el != null && el !== "";
                });
                result.push(mapping);
           }
        }
        this._addLineToTable();
        this.api.set('domain.mapping.custom', JSON.stringify({data: result}))
            .then((d) => {
                this._showMessage('saved', `[id="domain-mapping-table"]`);
            })
            .catch((d) => {
                this._showMessage('error', `[id="domain-mapping-table"]`);
                if(d.message) OC.Notification.show(PasswordsAdminSettings._translate(d.message));
            });
    }

    /**
     * Add a new line to the custom domain mapping table
     *
     * @param value
     * @private
     */
    _addLineToTable(value = "") {
        var table = document.getElementById('domain-mapping-table');
        
        var input = document.createElement("input");
        input.type = "text";
        input.className = "domain-mapping-custom";
        input.value = value;
        this._addDomainMappingEventListener(input);

        var td = document.createElement("td");
        td.appendChild(input);

        var tr = document.createElement("tr");
        tr.appendChild(td);

        table.appendChild(tr);
    }

    /**
     * Add event to custom domain mapping input fields
     *
     * @param value
     * @private
     */
    _addDomainMappingEventListener(element) {
      //  $('.domain-mapping-custom').off('change');
        $(element).on(
            'change',
            () => { this._onChangeCustomDomainMapping(); }
        );
    }
}

$(window).on('DOMContentLoaded', () => {
    let PwSettings = new PasswordsAdminSettings();
    PwSettings.initialize();
});