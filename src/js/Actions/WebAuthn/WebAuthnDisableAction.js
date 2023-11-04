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
import ToastService from "@js/Services/ToastService";

export default class WebAuthnDisableAction {

    run() {
        SettingsService.set('client.encryption.webauthn.enabled', false);
        ToastService.info('WebAuthnLoginDisabled');
    }
}