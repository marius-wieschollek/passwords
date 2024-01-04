/*
 * @copyright 2024 Passwords App
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the Passwords App
 * created by Marius David Wieschollek.
 */

import ClientService from "@js/Services/ClientService";
import MessageService from "@js/Services/MessageService";
import EncryptionManager from "@js/Manager/EncryptionManager";
import LoggingService from "@js/Services/LoggingService";
import SettingsService from "@js/Services/SettingsService";

export default class CheckPassphrase {

    /**
     * Check if the users current encryption passphrase
     * is still secure.
     *
     * @param {String} passphrase The encryption passphrase
     * @return {Promise<void>}
     */
    async run(passphrase) {
        if(!await this._isSecure(passphrase)) {
            this._forcePassphraseChange()
                .catch(LoggingService.catch)
                .then(() => {
                    SettingsService.set('client.encryption.passphrase.check', Date.now() / 1000);
                });
        }
    }

    /**
     *
     * @param {String} passphrase
     * @return {Promise<boolean>}
     * @private
     */
    async _isSecure(passphrase) {
        if(passphrase.length < 12) {
            return false;
        }

        if(SettingsService.get('client.encryption.passphrase.check') > ((Date.now() / 1000) - 2419200)) {
            return true;
        }

        let hashService    = /** @type {HashService} **/ await ClientService.getClient().getInstance('service.hash'),
            hash           = await hashService.getHash(passphrase),
            breachedHashes = await hashService.getBreachedHashes(hash.substring(0, 5));

        return breachedHashes.indexOf(hash) === -1;
    }

    /**
     *
     * @return {Promise<void>}
     * @private
     */
    async _forcePassphraseChange() {
        let result = false;

        do {
            await MessageService.alert('CSEPassphraseInsecureText', 'CSEPassphraseInsecureTitle');
            result = await EncryptionManager.updateGui();
        } while(result === false);
    }
}