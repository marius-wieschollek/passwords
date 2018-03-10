import $ from "jquery";
import Utility from "@js/Classes/Utility";

class Messages {

    /**
     *
     * @param notification
     */
    notification(notification) {
        return new Promise((resolve, reject) => {
            let $element = OC.Notification.show(Messages._translate(notification));

            setTimeout(function() {
                OC.Notification.hide($element);
                resolve();
            }, 10000)
        });
    }

    /**
     *
     * @param message
     * @param title
     * @returns {Promise}
     */
    alert(message, title = 'Alert') {
        return new Promise((resolve, reject) => {
            message = Messages._translate(message);
            title = Messages._translate(title);
            let callback = function() { resolve(); };

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
            message = Messages._translate(message);
            title = Messages._translate(title);
            let callback = function(success) { success ? resolve():reject(); };

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
            message = Messages._translate(message);
            title = Messages._translate(title);
            let callback = function(success) { success ? resolve():reject(); };

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
            message = Messages._translate(message);
            title = Messages._translate(title);
            let callback = function(success, value) { success ? resolve(value):reject(value); };

            OC.dialogs.prompt('', title, callback, true, message, isPassword);
            if(value !== null) {
                this._setDialogValue(value);
            }
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
        let html = '',
            id   = 'passwords-form-' + Math.round(Math.random() * 10);
        for(let name in form) {
            if(!form.hasOwnProperty(name)) continue;
            let field = form[name],
                value = field.value ? field.value:'',
                type  = field.type ? field.type:'text',
                label = Utility.translate(field.label ? field.label:name.capitalize());

            html += '<label>' + label + '</label><input type="' + type + '" value="' + value + '" name="' + name + '">';
        }

        if(message.length !== 0) message = '<div class="message">' + Utility.translate(message) + '</div>';
        html = '<form class="passwords-form" id="' + id + '">' + message.replace("\n",'<br>') + html + '</form>';

        return new Promise((resolve, reject) => {
            title = Messages._translate(title);
            let callback = function(success) {
                if(success) {
                    let serialized = $('#' + id).serializeArray(),
                        data       = {};

                    for(let i = 0; i < serialized.length; i++) {
                        let field = serialized[i];
                        data[field.name] = field.value
                    }

                    $('.oc-dialog, .oc-dialog-dim').remove();
                    resolve(data)
                } else {
                    reject()
                }
            };

            OC.dialogs.confirmHtml(html, title, callback, true);
        });
    }

    filePicker(title = 'Pick a file', mime, multiselect = false) {
        return new Promise((resolve) => {
            OC.dialogs.filepicker(title, (e,f) => {console.log(e,f);} , multiselect, mime, true, 1)
        });
    }

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
            setTimeout(() => { this._setDialogValue(value) }, 10)
        } else {
            $el.val(value);
        }
    }

    /**
     *
     * @param text
     * @returns {string}
     * @private
     */
    static _translate(text) {
        return Array.isArray(text) ? Utility.translate(text[0], text[1]):Utility.translate(text);
    }
}

let M = new Messages();

export default M;