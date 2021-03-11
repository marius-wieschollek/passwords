import { showMessage, showInfo, showSuccess, showWarning, showError } from '@nextcloud/dialogs'
import '@nextcloud/dialogs/styles/toast'
import Localisation                                                   from '@js/Classes/Localisation';

export default new class ToastService {

    info(title, options = {}) {
        return new Promise((resolve, reject) => {
            this._promisifyToast(options, resolve, reject);

            showInfo(Localisation.translateArray(title), options);
        })
    }

    message(title, options = {}) {
        return new Promise((resolve, reject) => {
            this._promisifyToast(options, resolve, reject);

            showMessage(Localisation.translateArray(title), options);
        })
    }

    success(title, options = {}) {
        return new Promise((resolve, reject) => {
            this._promisifyToast(options, resolve, reject);

            showSuccess(Localisation.translateArray(title), options);
        })
    }

    warning(title, options = {}) {
        return new Promise((resolve, reject) => {
            this._promisifyToast(options, resolve, reject);

            showWarning(Localisation.translateArray(title), options);
        })
    }

    error(title, options = {}) {
        return new Promise((resolve, reject) => {
            this._promisifyToast(options, resolve, reject);

            showError(Localisation.translateArray(title), options);
        })
    }

    _promisifyToast(options, resolve, reject) {
        options.onClick = () => {
            if(resolve) resolve(true);
        };
        options.onRemove = () => {
            if(reject) reject();
        };
    }
}