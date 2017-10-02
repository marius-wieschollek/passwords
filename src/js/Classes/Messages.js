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
            OC.dialogs.alert(this._translate(message), this._translate(title), function (success) {
                if (success) {resolve();}
                else {reject();}
            });
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
            OC.dialogs.info(this._translate(message), this._translate(title), function (success) {
                if (success) {resolve();}
                else {reject();}
            });
        });
    }

    /**
     *
     * @param message
     * @param title
     */
    confirm(message, title = 'Confirm') {
        return new Promise((resolve, reject) => {
            OC.dialogs.confirm(this._translate(message), this._translate(title), function (success) {
                if (success) {resolve();}
                else {reject();}
            });
        });
    }

    _translate(text) {
        if(Array.isArray(text)) {
            return t('passwords', text[0], text[1]);
        }
        return t('passwords', text);
    }
}

const PwMessages = new Messages();

export default PwMessages