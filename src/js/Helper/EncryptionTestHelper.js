import API from '@js/Helper/api';
import sodium from 'libsodium-wrappers';
import Encryption from '@js/ApiClient/Encryption';
import EnhancedApi from '@js/ApiClient/EnhancedApi';
import SettingsManager from '@js/Manager/SettingsManager';
import DeferredActivationService from "@/js/Service/DeferredActivationService";

class EncryptionTestHelper {
    constructor() {
        this.encryption = new Encryption();
        this.key = null;
    }

    /**
     *
     */
    async initTests() {
        if(await DeferredActivationService.check('encryption-tests')) return;

        if(!API.isAuthorized) {
            console.log('Encryption tests scheduled after login');
            setTimeout(() => { this.initTests(); }, 5000);
            return;
        }

        let testExecuted = SettingsManager.get('local.test.e2e.executed', false),
            testTimeout  = SettingsManager.get('local.test.e2e.timeout', 0);

        if(testTimeout !== 0) {
            SettingsManager.set('local.test.e2e.timeout', testTimeout - 1);
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
            SettingsManager.set('local.test.e2e.executed', true);
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
        return await this.testObjects(await API.listPasswords(), 'password');
    }

    /**
     *
     * @returns {Promise<*>}
     */
    async testFolders() {
        return await this.testObjects(await API.listFolders(), 'folder');
    }

    /**
     *
     * @returns {Promise<*>}
     */
    async testTags() {
        return await this.testObjects(await API.listTags(), 'tag');
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
            if(type === 'password') object = EnhancedApi.flattenPassword(object);

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
        result.app = '2019.3.0';
        result.api = API.versionString;
        result.apps = Object.keys(oc_appswebroots);

        if(result.error) {
            result.error = {
                file   : result.error.fileName,
                line   : result.error.lineNumber,
                column : result.error.columnNumber,
                message: result.error.message,
                stack  : result.error.stack.split('\n')
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
                    SettingsManager.set('local.test.e2e.executed', true);
                } else {
                    SettingsManager.set('local.test.e2e.timeout', 10);
                }
            },
            true
        );
    }
}

let ETH = new EncryptionTestHelper();

export default ETH;