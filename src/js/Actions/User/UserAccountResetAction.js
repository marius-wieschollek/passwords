/*
 * @copyright 2023 Passwords App
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the Passwords App
 * created by Marius David Wieschollek.
 */

import API from '@js/Helper/api';
import MessageService from "@js/Services/MessageService";
import LoggingService from "@js/Services/LoggingService";

export default class UserAccountResetAction {

    async run() {
        let confirmed = await MessageService.confirm(
            'Do you want to delete all your settings, passwords, folders and tags?\nIt will NOT be possible to undo this.',
            'DELETE EVERYTHING',
            true
        );

        if(confirmed) {
            return await this._requestUserAccountReset();
        }

        return confirmed;
    }


    async _requestUserAccountReset() {
        try {
            let response = await API.resetUserAccount();
            if(response.status === 'accepted') {
                let message = ['Enter "{code}" to reset your account and delete everything.', {code: response.code}];

                let code;
                try {
                    code = await MessageService.prompt('Code', 'Account reset requested', message, null, null, true);
                } catch(e) {
                    return false;
                }

                return await this._executeUserAccountReset(code);
            } else {
                this._showError(new Error('UserAccountResetInvalidStatus'));
            }
        } catch(e) {
            this._showError(e);
        }

        return false;
    }

    _showError(e) {
        LoggingService.error(e);
        MessageService.alert(e.message ? e.message:'Invalid reset code');
    }

    async _executeUserAccountReset(code) {
        try {
            let response = await API.resetUserAccount(code);

            if(response.status === 'ok') {
                window.localStorage.removeItem('pwFolderIcon');
                location.href = location.href.replace(location.hash, '');
                return true;
            } else {
                this._showError(new Error('UserAccountResetInvalidStatus'));
            }
        } catch(e) {
            this._showError(e);
        }

        return false;
    }
}