import API from '@js/Helper/api';
import Encryption from "@/js/ApiClient/Encryption";
import SettingsManager from "@js/Manager/SettingsManager";

class EncryptionTestHelper {
    constructor() {
        this.encryption = new Encryption();
        this.password = 'EnCrYpT10Np@$$WÖrD';
    }

    initTests() {
        if(new Date().getTime() > 1533247200000) return;
        let testExecuted = SettingsManager.get('encryption.tests.executed', false),
            testTimeout = SettingsManager.get('encryption.tests.timeout', 0);

        if(testTimeout !== 0) {
            SettingsManager.set('encryption.tests.timeout', testTimeout-1);
        } else if(!testExecuted) {
            setTimeout(() => {this.runTests();}, 15000);
        }
    }

    async runTests() {
        try {
            let result = await this.testEncryption();
            if(result !== true) return this.handleError(result);
            result = await this.testPasswords();
            if(result !== true) return this.handleError(result);
            result = await this.testFolders();
            if(result !== true) return this.handleError(result);
            result = await this.testTags();
            if(result !== true) return this.handleError(result);
            SettingsManager.set('encryption.tests.executed', true);
            console.log('Encryption tests ran successfully');
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
        let text = '', password = '✓ à la mode';
        for(let i = 0; i < 96; i++) text += i + ': ✓ à la mode | ';

        try {
            let encData = await this.encryption.encrypt(text, password);

            try {
                let json = JSON.stringify(encData);
                let data = JSON.parse(json);
                let decData = await this.encryption.decrypt(data, password);
                if(text !== decData) {
                    return {
                        type: 'test',
                        stage : 'validate',
                        error: "Decrypted Data Missmatch",
                        data : [text, encData, decData, json, data]
                    };
                }
            } catch(e) {
                return {type: 'test', stage : 'decrypt', error: e};
            }
        } catch(e) {
            return {type: 'test', stage : 'encrypt', error: e};
        }

        return true;
    }

    async testPasswords() {
        return await this.testObjects(await API.listPasswords(), 'password');
    }

    async testFolders() {
        return await this.testObjects(await API.listFolders(), 'folder');
    }

    async testTags() {
        return await this.testObjects(await API.listTags(), 'tag');
    }

    async testObjects(db, type) {
        for(let i in db) {
            if(!db.hasOwnProperty(i)) continue;
            let object = db[i];

            try {
                let encrypted = await this.encryption.encryptObject(object, this.password, type);

                try {
                    await this.encryption.decryptObject(encrypted, this.password, type);
                } catch(e) {
                    return {type: type, stage: 'decrypt', id: i, error: e};
                }
            } catch(e) {
                return {type: type, stage: 'encrypt', id: i, error: e};
            }
        }

        return true;
    }


    handleError(result) {
        result.userAgent = navigator.userAgent;
        result.protocol = location.protocol;
        result.crypto = !!window.crypto;
        result.textencoder = !!window.TextEncoder;
        result.apps = Object.keys(oc_appswebroots);
        let json = JSON.stringify(result);

        let html = '<div><p>'
                   + 'Passwords is currently testing stronger encryption to keep<br>your passwords safe. '
                   + '<b>These tests failed with your browser.</b><br>'
                   + 'To help us identify and fix the issues, we would ask you to<br>open an issue on our '
                   + '<a href="https://github.com/marius-wieschollek/passwords/issues" target="_blank" style="text-decoration:underline">public issue tracker</a> '
                   + 'or send us an<br>'
                   + '<a href="' + atob('bWFpbHRvOnBhc3N3b3Jkcy5lbmNyeXB0aW9udGVzdEBtZG5zLmV1') + '" target="_blank" style="text-decoration:underline">email</a> '
                   + 'with the following data attached:</p><br>'
                   + '<div style="max-width:360px;word-break:break-all;background:#f7f7f7;padding:5px;cursor:text;">' + btoa(json) + '</div>'
                   + '</div>';

        OC.dialogs.confirmHtml(
            html,
            'Encryption Tests Failed',
            (d) => {
                if(d) {
                    SettingsManager.set('encryption.tests.executed', true);
                } else {
                    SettingsManager.set('encryption.tests.timeout', 10);
                }
            },
            true
        );
    }
}

let ETH = new EncryptionTestHelper();

export default ETH;