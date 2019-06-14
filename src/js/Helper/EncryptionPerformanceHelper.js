import sodium from 'libsodium-wrappers';
import {Encryption} from 'passwords-client';

class EncryptionPerformanceHelper {

    constructor() {
        this.encryption = new Encryption();
        this.key = null;
    }

    async runTests() {
        await this.encryption.ready;

        let salt = this.encryption._generateRandom(sodium.crypto_pwhash_SALTBYTES);
        this.key = this.encryption._passwordToKey('EnCrYpT!0nP@$$w0rD', salt);
        this.encryption.getKeychain('EnCrYpT!0nP@$$w0rD');

        let object = {
            "username"    : "user",
            "password"    : "password",
            "label"       : "name",
            "url"         : "https://example.com",
            "notes"       : "Notes",
            "customFields": "[{\"label\":\"field\",\"type\":\"text\",\"value\":\"value\"}]",
            "folder"      : "00000000-0000-0000-0000-000000000000",
            "edited"      : 0,
            "favorite"    : false
        };

        for(let i=0; i< 128; i++) object.notes += ' - Notes'
        let result = await this._runPerformanceTest(object);

        return {result};
    }

    /**
     *
     * @param object
     * @returns {Promise<number>}
     * @private
     */
    async _runPerformanceTest(object) {
        let startTime = Date.now(),
            counter   = 0;

        while(Date.now() - startTime <= 9000) {
            let encrypted = this.encryption.encryptObject(object, 'password');
            this.encryption.decryptObject(encrypted, 'password');
            counter++;
        }

        return counter;
    }
}

export default new EncryptionPerformanceHelper();