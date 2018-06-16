import $ from 'jquery';
import Vue from 'vue';
import Localisation from '@js/Classes/Localisation';

class Messages {

    /**
     *
     * @param notification
     */
    notification(notification) {
        return new Promise((resolve) => {
            let $element = OC.Notification.show(Localisation.translateArray(notification));

            setTimeout(() => {
                OC.Notification.hide($element);
                resolve({});
            }, 10000);
        });
    }

    /**
     *
     * @param message
     * @param title
     * @returns {Promise}
     */
    alert(message, title = 'Alert') {
        return new Promise((resolve) => {
            message = Localisation.translateArray(message);
            title = Localisation.translateArray(title);
            let callback = function() { resolve({}); };

            OC.dialogs.alert(message, title, callback, true);
        });
    }

    /**
     *
     * @param message
     * @param title
     * @returns {Promise}
     */
    info(message, title = 'Info') {
        return new Promise((resolve, reject) => {
            message = Localisation.translateArray(message);
            title = Localisation.translateArray(title);
            let callback = function(success) { success ? resolve({}):reject({}); };

            OC.dialogs.info(message, title, callback, true);
        });
    }

    /**
     *
     * @param message
     * @param title
     */
    confirm(message, title = 'Confirm') {
        return new Promise((resolve, reject) => {
            message = Localisation.translateArray(message);
            title = Localisation.translateArray(title);
            let callback = function(success) { success ? resolve({}):reject({}); };

            OC.dialogs.confirm(message, title, callback, true);
        });
    }

    /**
     *
     * @param message
     * @param title
     * @param value
     * @param isPassword
     * @returns {Promise<any>}
     */
    prompt(message, title = 'Prompt', value = null, isPassword = false) {
        return new Promise((resolve, reject) => {
            message = Localisation.translateArray(message);
            title = Localisation.translateArray(title);
            let callback = function(success, value) { success ? resolve(value):reject(value); };

            OC.dialogs.prompt('', title, callback, true, message, isPassword);
            if(value !== null) this._setDialogValue(value);
        });
    }

    /**
     *
     * @param form
     * @param title
     * @param message
     * @returns {Promise<any>}
     */
    form(form, title = 'Form', message = '') {
        let id   = `passwords-form-${Math.round(Math.random() * 10)}`,
            html = `<div id="${id}"></div>`;

        return new Promise(async (resolve, reject) => {
            let callback = (success) => {
                if(success) {
                    let data = Form.getFormData();
                    if(!data) throw new Error('Invalid Form Data');

                    $('.oc-dialog, .oc-dialog-dim').remove();
                    resolve(data);
                } else {
                    reject({});
                }
            };

            title = Localisation.translateArray(title);
            OC.dialogs.confirmHtml(html, title, callback, true);

            let FormTemplate  = await import(/* webpackChunkName: "Form" */ '@vue/Components/Form.vue'),
                FormComponent = Vue.extend(FormTemplate.default),
                Form          = new FormComponent({propsData: {form, message, id}});

            this._loadForm(id, Form);
        });
    }

    /**
     *
     * @param title
     * @param mime
     * @param multiselect
     * @returns {Promise<any>}
     */
    filePicker(title = 'Pick a file', mime, multiselect = false) {
        return new Promise((resolve) => {
            OC.dialogs.filepicker(title, (e, f) => {resolve(e, f);}, multiselect, mime, true, 1);
        });
    }

    /**
     *
     * @param title
     * @returns {*}
     */
    folderPicker(title) {
        return this.filepicker(title, 'httpd/unix-directory', false);
    }

    /**
     * Sets the value of an input field because Nextcloud does not support this
     *
     * @param value
     * @private
     */
    _setDialogValue(value) {
        let $el = $('.oc-dialog-content input');
        if($el.length === 0) {
            setTimeout(() => { this._setDialogValue(value); }, 10);
        } else {
            $el.val(value);
        }
    }

    /**
     *
     * @param id
     * @param form
     * @private
     */
    _loadForm(id, form) {
        let $el = document.getElementById(id);
        if($el === null) {
            setTimeout(() => { this._loadForm(id, form); }, 10);
        } else {
            form.$mount(`#${id}`);

            let buttons = document.querySelectorAll('.oc-dialog-buttonrow.twobuttons button');
            buttons[0].innerText = Localisation.translate('Cancel');
            buttons[1].innerText = Localisation.translate('Ok');
        }
    }
}

let M = new Messages();

export default M;