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
import {loadState} from "@nextcloud/initial-state";
import Logger from "@js/Classes/Logger";
import ToastService from "../../Services/ToastService";

export default class WebAuthnAuthorizeAction {

    static hasBeenAttempted = false;

    isAvailable() {
        return !WebAuthnAuthorizeAction.hasBeenAttempted && !!window.PasswordCredential && SettingsService.get('local.encryption.webauthn.enabled');
    }

    async run() {
        WebAuthnAuthorizeAction.hasBeenAttempted = true;
        let username = loadState('passwords', 'api-user', null) + '.passwordsapp@' + location.host;

        try {
            let data = await navigator.credentials.get(
                new PasswordCredential({password: true, id: username})
            );

            if(!data) {
                this.disable();
                return null;
            }

            return data.password;
        } catch(e) {
            Logger.error(e);
        }

        return null;
    }

    disable() {
        SettingsService.set('local.encryption.webauthn.enabled', false);
        ToastService.info('WebAuthnLoginDisabled');
    }
}