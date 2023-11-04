/*
 * @copyright 2023 Passwords App
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the Passwords App
 * created by Marius David Wieschollek.
 */

import MessageService from "@js/Services/MessageService";
import LocalisationService from "@js/Services/LocalisationService";
import SettingsService from "@js/Services/SettingsService";
import ToastService from "@js/Services/ToastService";
import {getCurrentUser} from "@nextcloud/auth";
import LoggingService from "@js/Services/LoggingService";

export default class WebAuthnInitializeAction {

    static isWebauthnPasswordAvailable() {
        return !!window.PasswordCredential;
    }

    async run(password = null) {
        if(!WebAuthnInitializeAction.isWebauthnPasswordAvailable()) {
            return;
        }

        if(password === null) {
            let data = await this._showSetupDialog();
            password = data.password;
        }

        try {
            await this._storeEncryptionPassphrase(password);

            SettingsService.set('client.encryption.webauthn.enabled', true);
            ToastService.success('WebauthnLoginSetupSuccess');
        } catch(e) {
            LoggingService.error(e);
        }
    }

    async _storeEncryptionPassphrase(password) {
        let name    = LocalisationService.translate('Passwords App Encryption Passphrase'),
            id      = `${getCurrentUser().uid}.passwordsapp@${location.host}`,
            iconURL = SettingsService.get('server.theme.app.icon');


        const passwordCredential = new PasswordCredential({id, password, name, iconURL});
        await navigator.credentials.store(passwordCredential);
    }

    _showSetupDialog() {
        return MessageService.form(
            {
                password      : {
                    label    : 'WebauthnEncryptionPassphrase',
                    type     : 'password',
                    button   : 'toggle',
                    minlength: 12,
                    required : true,
                    validator: (value, fields) => {
                        return value !== fields.oldPassword && value.length >= 12;
                    }
                },
                repeatPassword: {
                    label    : 'WebauthnEncryptionPassphraseRepeat',
                    type     : 'password',
                    button   : 'toggle',
                    required : true,
                    validator: (value, fields) => {
                        return value === fields.password;
                    }
                }
            },
            'WebauthnEncryptionSetupTitle',
            'WebauthnEncryptionPassphraseText'
        );
    }
}