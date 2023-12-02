/*
 * @copyright 2023 Passwords App
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the Passwords App
 * created by Marius David Wieschollek.
 */

import '@nextcloud/dialogs/style.css'
import Localisation from '@js/Classes/Localisation';
export default new class ToastService {

    info(title, options = {}) {
        return new Promise((resolve) => {
            this._promisifyToast(options, resolve);
            this._runNextcloudDialog(title, options, 'showInfo');
        });
    }

    message(title, options = {}) {
        return new Promise((resolve) => {
            this._promisifyToast(options, resolve);
            this._runNextcloudDialog(title, options, 'showMessage');
        });
    }

    success(title, options = {}) {
        return new Promise((resolve) => {
            this._promisifyToast(options, resolve);
            this._runNextcloudDialog(title, options, 'showSuccess');
        });
    }

    warning(title, options = {}) {
        return new Promise((resolve) => {
            this._promisifyToast(options, resolve);
            this._runNextcloudDialog(title, options, 'showWarning');
        });
    }

    error(title, options = {}) {
        return new Promise((resolve) => {
            this._promisifyToast(options, resolve);
            this._runNextcloudDialog(title, options, 'showError');
        });
    }

    _promisifyToast(options, resolve) {
        options.onClick = () => {
            if(resolve) resolve(true);
        };
        options.onRemove = () => {
            if(resolve) resolve(false);
        };
    }

    _runNextcloudDialog(title, options, method) {
        import(/* webpackChunkName: "NcDialogs" */  `@nextcloud/dialogs`)
            .then((module) => {
                module[method](Localisation.translateArray(title), options);
            });
    }
};