/*
 * @copyright 2023 Passwords App
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the Passwords App
 * created by Marius David Wieschollek.
 */

import SettingsService from "@js/Services/SettingsService";
import LoggingService from "@js/Services/LoggingService";
import {getCurrentUser} from "@nextcloud/auth";

export default class WebAuthnAuthorizeAction {

    static hasBeenAttempted = false;

    isAvailable() {
        return !WebAuthnAuthorizeAction.hasBeenAttempted && !!window.PasswordCredential && SettingsService.get('client.encryption.webauthn.enabled');
    }

    async run() {
        if(!this.isAvailable() || WebAuthnAuthorizeAction.hasBeenAttempted) {
            return null;
        }

        WebAuthnAuthorizeAction.hasBeenAttempted = true;
        try {
            let username = `${getCurrentUser().uid}.passwordsapp@${location.host}`;

            let data = await navigator.credentials.get(
                new PasswordCredential({password: true, id: username})
            );

            if(data && data.password) {
                return data.password;
            }
        } catch(e) {
            LoggingService.error(e);
        }

        return null;
    }
}