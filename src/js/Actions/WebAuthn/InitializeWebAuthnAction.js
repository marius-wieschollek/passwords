/*
 * @copyright 2023 Passwords App
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the Passwords App
 * created by Marius David Wieschollek.
 */

import Messages from "@js/Classes/Messages";
import {loadState} from "@nextcloud/initial-state";
import Localisation from "@js/Classes/Localisation";
import SettingsService from "@js/Services/SettingsService";
import ToastService from "@js/Services/ToastService";

export default class InitializeWebAuthnAction {

    static isWebauthnPasswordAvailable() {
        return !!window.PasswordCredential;
    }

    async run() {
        if(!InitializeWebAuthnAction.isWebauthnPasswordAvailable()) {
            return;
        }

        let data = await this._showSetupDialog();
        await this._storeEncryptionPassphrase(data);

        SettingsService.set('local.encryption.webauthn.enabled', true);
        await ToastService.success('WebauthnLoginSetupSuccess');
    }

    async _storeEncryptionPassphrase(data) {
        let username = loadState('passwords', 'api-user', null),
            label    = Localisation.translate('Passwords App Encryption Passphrase');


        const passwordCredential = new PasswordCredential(
            {
                id      : username + '.passwordsapp@' + location.host,
                password: data.password,
                name    : label,
                iconURL : SettingsService.get('server.theme.app.icon')
            }
        );

        await navigator.credentials.store(passwordCredential);
    }

    _showSetupDialog() {
        return Messages.form(
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
            'WebauthnEncryptionPassphraseText'
        );
    }
}