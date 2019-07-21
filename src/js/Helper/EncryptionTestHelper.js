import API from '@js/Helper/api';
import sodium from 'libsodium-wrappers';
import {Encryption} from 'passwords-client';
import SettingsService from '@js/Services/SettingsService';
import DeferredActivationService from '@js/Services/DeferredActivationService';

class EncryptionTestHelper {
    constructor() {
        this.encryption = new Encryption();
        this.key = null;
    }

    /**
     *
     */
    async initTests() {
        if(!await DeferredActivationService.check('encryption-tests')) return;

        if(!API.isAuthorized) {
            console.log('Encryption tests scheduled after login');
            setTimeout(() => { this.initTests(); }, 5000);
            return;
        }

        let testExecuted = SettingsService.get('local.test.e2e.executed', false),
            testTimeout  = SettingsService.get('local.test.e2e.timeout', 0);

        if(testTimeout !== 0) {
            SettingsService.set('local.test.e2e.timeout', testTimeout - 1);
        } else if(!testExecuted) {
            setTimeout(() => {this.runTests();}, 15000);
        }
    }

    /**
     *
     * @returns {Promise<*>}
     */
    async runTests() {
        try {
            await this.encryption.ready;

            let salt = this.encryption._generateRandom(sodium.crypto_pwhash_SALTBYTES);
            this.key = this.encryption._passwordToKey('EnCrYpT!0nP@$$w0rD', salt);
            this.encryption.getKeychain('EnCrYpT!0nP@$$w0rD');

            let result = await this.testEncryption();
            if(result !== true) return this.handleError(result);
            result = await this.testPasswords();
            if(result !== true) return this.handleError(result);
            result = await this.testFolders();
            if(result !== true) return this.handleError(result);
            result = await this.testTags();
            if(result !== true) return this.handleError(result);
            SettingsService.set('local.test.e2e.executed', true);
            console.info('Encryption tests ran successfully');
            return true;
        } catch(e) {
            this.handleError({error: e});
            return false;
        }
    }

    /**
     *
     * @returns {Promise<*>}
     */
    async testEncryption() {
        let text = '';
        for(let i = 0; i < 96; i++) text += `${i}: ✓ à la mode | `;

        try {
            let encData = await this.encryption.encryptString(text, this.key);

            try {
                let json    = JSON.stringify(encData),
                    data    = JSON.parse(json),
                    decData = await this.encryption.decryptString(data, this.key);
                if(text !== decData) {
                    return {
                        type  : 'test',
                        stage : 'validate',
                        reason: 'Decrypted Data Mismatch',
                        data  : {text, encData, decData, json, data}
                    };
                }
            } catch(e) {
                return {type: 'test', stage: 'decrypt', error: e};
            }
        } catch(e) {
            return {type: 'test', stage: 'encrypt', error: e};
        }

        return true;
    }

    /**
     *
     * @returns {Promise<*|boolean>}
     */
    async testPasswords() {
        let type = 'password', passwords;
        try {
            passwords = await API.listPasswords();
        } catch(e) {
            return {type, stage: 'fetch', error: e};
        }

        return await this.testObjects(passwords, type);
    }

    /**
     *
     * @returns {Promise<*>}
     */
    async testFolders() {
        let type = 'folder', folders;
        try {
            folders = await API.listFolders();
        } catch(e) {
            return {type, stage: 'fetch', error: e};
        }

        return await this.testObjects(folders, type);
    }

    /**
     *
     * @returns {Promise<*>}
     */
    async testTags() {
        let type = 'tag', tags;
        try {
            tags = await API.listTags();
        } catch(e) {
            return {type, stage: 'fetch', error: e};
        }
        return await this.testObjects(tags, type);
    }

    /**
     *
     * @param db
     * @param type
     * @returns {Promise<*>}
     */
    async testObjects(db, type) {
        for(let i in db) {
            if(!db.hasOwnProperty(i)) continue;
            let object = db[i];
            if(type === 'password') object = API.flattenPassword(object);

            try {
                let encrypted = await this.encryption.encryptObject(object, type);

                try {
                    await this.encryption.decryptObject(encrypted, type);
                } catch(e) {
                    return {type, stage: 'decrypt', id: i, error: e};
                }
            } catch(e) {
                return {type, stage: 'encrypt', id: i, error: e};
            }
        }

        return true;
    }

    /**
     *
     * @param result
     */
    handleError(result) {
        result.userAgent = navigator.userAgent;
        result.protocol = location.protocol;
        result.crypto = !!window.crypto;
        result.textencoder = !!window.TextEncoder;
        result.app = process.env.APP_VERSION;
        result.api = API.getClientVersion();
        result.apps = Object.keys(oc_appswebroots);

        if(result.error) {
            let stack = ['no stack'];
            if(result.error.stack) {
                stack = result.error.stack.split('\n');
            }

            result.error = {
                file   : result.error.fileName,
                line   : result.error.lineNumber,
                column : result.error.columnNumber,
                message: result.error.message,
                stack
            };
        }

        let base64 = sodium.to_base64(JSON.stringify(result)).replace(/_/g, '/'),
            link   = atob('bWFpbHRvOnBhc3N3b3Jkcy5lbmNyeXB0aW9udGVzdEBtZG5zLmV1'),
            html   = `<div><p>Passwords is testing a new encryption.<br>
<b>This test failed with your browser.</b>
<br>Please open a ticket on our <a href="https://github.com/marius-wieschollek/passwords/issues" target="_blank" style="text-decoration:underline">issue tracker</a> 
or send us an <a href="${link}?subject=Encryption%20Test&body=${encodeURIComponent(base64)}" target="_blank" style="text-decoration:underline">email</a>
with this data attached:</p><br><div style="max-width:250px;max-height:200px;overflow:auto;word-break:break-all;background:#f7f7f7;padding:5px;cursor:text;">${base64}</div></div>`;

        OC.dialogs.confirmHtml(
            html,
            'Encryption Tests Failed',
            (d) => {
                if(d) {
                    SettingsService.set('local.test.e2e.executed', true);
                } else {
                    SettingsService.set('local.test.e2e.timeout', 10);
                }
            },
            true
        );
    }
}

let ETH = new EncryptionTestHelper();

export default ETH;