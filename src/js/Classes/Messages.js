class Messages {

    /**
     *
     * @param notification
     */
    notification(notification) {
        let $element = OC.Notification.show(this._translate(notification));

        setTimeout(function () {
            OC.Notification.hide($element);
        }, 10000)
    }

    /**
     *
     * @param message
     * @param title
     * @returns {Promise}
     */
    alert(message, title = 'Alert') {
        return new Promise((resolve, reject) => {
            message = this._translate(message);
            title = this._translate(title);
            let callback = function (success) { success ? resolve():reject(); };

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
            message = this._translate(message);
            title = this._translate(title);
            let callback = function (success) { success ? resolve():reject(); };

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
            message = this._translate(message);
            title = this._translate(title);
            let callback = function (success) { success ? resolve():reject(); };

            OC.dialogs.confirm(message, title, callback, true);
        });
    }

    prompt(message, title = 'Prompt', isPassword = false) {
        return new Promise((resolve, reject) => {
            message = this._translate(message);
            title = this._translate(title);
            let callback = function (success, value) { success ? resolve(value):reject(value); };
            let name = 'i' + Math.random();

            OC.dialogs.prompt('', title, callback, true, message, isPassword);
        });
    }

    _translate(text) {
        if (Array.isArray(text)) {
            return t('passwords', text[0], text[1]);
        }
        return t('passwords', text);
    }
}

const PwMessages = new Messages();

export default new Messages()