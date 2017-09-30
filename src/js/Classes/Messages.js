class Messages {

    /**
     *
     * @param notification
     */
    notification(notification) {
        let $element = OC.Notification.show(notification);

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
    alert(message, title = 'Message') {
        return new Promise((resolve, reject) => {
            OC.dialogs.alert(message, title, function (success) {
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
    info(message, title = 'Message') {
        return new Promise((resolve, reject) => {
            OC.dialogs.info(message, title, function (success) {
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
    confirm(message, title = 'Message') {
        return new Promise((resolve, reject) => {
            OC.dialogs.confirm(message, title, function (success) {
                if (success) {resolve();}
                else {reject();}
            });
        });
    }
}

const PwMessages = new Messages();

export default PwMessages