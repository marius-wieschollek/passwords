import Vue from 'vue';
import Localisation from '@js/Classes/Localisation';
import ToastService from '@js/Services/ToastService';
export default new class Messages {

    /**
     *
     * @param notification
     */
    notification(notification) {
        return new Promise((resolve) => {
            ToastService
                .message(notification, {timeout: 10000})
                .then(resolve)
                .catch(resolve);
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

            OC.dialogs.alert(message, title, resolve, true);
        });
    }

    /**
     *
     * @param message
     * @param title
     * @returns {Promise}
     */
    info(message, title = 'Info') {
        return new Promise((resolve) => {
            message = Localisation.translateArray(message);
            title = Localisation.translateArray(title);

            OC.dialogs.info(message, title, resolve, true);
        });
    }

    /**
     *
     * @param message
     * @param title
     * @param booleanResponse
     */
    confirm(message, title = 'Confirm', booleanResponse = false) {
        return new Promise((resolve, reject) => {
            message = Localisation.translateArray(message);
            title = Localisation.translateArray(title);
            let callback = function(success) {
                if(booleanResponse) {
                    resolve(success === true);
                } else {
                    success ? resolve({}):reject({});
                }
            };

            OC.dialogs.confirm(message, title, callback, true);
        });
    }

    /**
     *
     * @param message
     * @param title
     * @param placeholder
     * @param value
     * @param label
     * @param isPassword
     * @returns {Promise<any>}
     */
    prompt(label, title = 'Prompt', message = null, placeholder = null, value = null, isPassword = false) {
        return new Promise((resolve, reject) => {
            let callback = function(success, value) { success ? resolve(value):reject(value); };
            title = Localisation.translateArray(title);
            label = Localisation.translateArray(label);

            if(message !== null) {
                message = Localisation.translateArray(message);
            } else {
                message = '';
            }

            OC.dialogs.prompt(message, title, callback, true, label, isPassword);
            if(value !== null) this._setDialogValue(value);
            if(placeholder !== null) {
                placeholder = Localisation.translateArray(placeholder);
                this._setDialogPlaceholder(placeholder);
            }
        });
    }

    /**
     *
     * @param {Object} form
     * @param {String} title
     * @param {String} message
     * @param {String} name
     * @returns {Promise<any>}
     */
    form(form, title = 'Form', message = '', name = '') {
        let id   = `passwords-form-${Math.round(Math.random() * 10)}`,
            html = `<div id="${id}"></div>`;

        return new Promise(async (resolve, reject) => {
            let callback = (success) => {
                if(success) {
                    let data = Form.getFormData();
                    if(!data) throw new Error('Invalid Form Data');

                    document
                        .querySelectorAll('.oc-dialog, .oc-dialog-dim')
                        .forEach((element) => {
                            element.remove();
                        });
                    resolve(data);
                } else {
                    reject({});
                }
            };

            title = Localisation.translateArray(title);
            OC.dialogs.confirmHtml(html, title, callback, true);

            let FormTemplate  = await import(/* webpackChunkName: "Form" */ '@vue/Components/Form.vue'),
                FormComponent = Vue.extend(FormTemplate.default),
                Form          = new FormComponent({propsData: {form, message, id, name}});

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
    ncFilePicker(title = 'Pick a file', mime, multiselect = false) {
        return new Promise((resolve) => {
            OC.dialogs.filepicker(title, (e, f) => {resolve(e, f);}, multiselect, mime, true, 1);
        });
    }

    /**
     *
     * @param title
     * @returns {*}
     */
    ncFolderPicker(title) {
        return this.ncFilePicker(title, 'httpd/unix-directory', false);
    }

    /**
     * Sets the value of an input field because Nextcloud does not support this
     *
     * @param value
     * @private
     */
    _setDialogValue(value) {
        let $el = document.querySelectorAll('.oc-dialog-content input');
        if($el.length === 0) {
            setTimeout(() => { this._setDialogValue(value); }, 10);
        } else {
            $el.forEach((element) => {
                element.value = value;
            });
        }
    }

    /**
     * Sets the placeholder of an input field because Nextcloud does not support this
     *
     * @param value
     * @private
     */
    _setDialogPlaceholder(value) {
        let $el = document.querySelectorAll('.oc-dialog-content input');
        if($el.length === 0) {
            setTimeout(() => { this._setDialogPlaceholder(value); }, 10);
        } else {
            $el.forEach((element) => {
                element.setAttribute('placeholder', value);
            });
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