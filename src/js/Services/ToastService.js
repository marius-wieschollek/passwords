/*
 * @copyright 2023 Passwords App
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the Passwords App
 * created by Marius David Wieschollek.
 */

import '@nextcloud/dialogs/styles/toast';
import Localisation from '@js/Classes/Localisation';

export default new class ToastService {

    info(title, options = {}) {
        return new Promise((resolve, reject) => {
            this._promisifyToast(options, resolve, reject);
            this._runNextcloudDialog(title, options, 'showInfo');
        });
    }

    message(title, options = {}) {
        return new Promise((resolve, reject) => {
            this._promisifyToast(options, resolve, reject);
            this._runNextcloudDialog(title, options, 'showMessage');
        });
    }

    success(title, options = {}) {
        return new Promise((resolve, reject) => {
            this._promisifyToast(options, resolve, reject);
            this._runNextcloudDialog(title, options, 'showSuccess');
        });
    }

    warning(title, options = {}) {
        return new Promise((resolve, reject) => {
            this._promisifyToast(options, resolve, reject);
            this._runNextcloudDialog(title, options, 'showWarning');
        });
    }

    error(title, options = {}) {
        return new Promise((resolve, reject) => {
            this._promisifyToast(options, resolve, reject);
            this._runNextcloudDialog(title, options, 'showError');
        });
    }

    _promisifyToast(options, resolve, reject) {
        options.onClick = () => {
            if(resolve) resolve(true);
        };
        options.onRemove = () => {
            if(reject) reject();
        };
    }

    _runNextcloudDialog(title, options, method) {
        import(/* webpackChunkName: "NcDialogs" */  `@nextcloud/dialogs`)
            .then((module) => {
                module[method](Localisation.translateArray(title), options);
            });
    }
};