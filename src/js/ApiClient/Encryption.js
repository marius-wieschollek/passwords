import sodium from "libsodium-wrappers";

export default class Encryption {

    constructor() {
        this.fields = {
            password: ['url', 'label', 'notes', 'password', 'username', 'customFields'],
            folder  : ['label'],
            tag     : ['label', 'color']
        };
        this._enabled = null;
        this._keychain = {};
        this.ready();
    }

    // noinspection JSMethodCanBeStatic
    async ready() {
        await sodium.ready;
    }

    /**
     * Encrypts an object
     *
     * @param object
     * @param type
     * @returns {Promise<*>}
     */
    async encryptObject(object, type) {
        if(!this.fields.hasOwnProperty(type)) throw new Error('Invalid object type');
        let fields = this.fields[type];

        for(let i = 0; i < fields.length; i++) {
            let field = fields[i],
                data  = object[field];

            if(data.length === 0) continue;
            object[field] = await this.encrypt(data);
        }

        return object;
    }

    /**
     * Decrypts an object
     *
     * @param object
     * @param type
     * @returns {Promise<*>}
     */
    async decryptObject(object, type) {
        if(!this.fields.hasOwnProperty(type)) throw new Error('Invalid object type');
        let fields = this.fields[type];

        for(let i = 0; i < fields.length; i++) {
            let field = fields[i],
                data  = object[field];

            if(data.length === 0) continue;
            object[field] = await this.decrypt(data);
        }

        return object;
    }

    /**
     * @param message
     *
     * @param key
     * @returns {Promise<string>}
     */
    async encrypt(message, key) {
        let nonce     = this._generateRandom(sodium.crypto_secretbox_NONCEBYTES),
            encrypted = new Uint8Array([...nonce, ...sodium.crypto_secretbox_easy(message, nonce, key)]);

        return sodium.to_base64(encrypted);
    }

    // noinspection JSMethodCanBeStatic
    /**
     * @param encodedString
     *
     * @param key
     * @returns {Promise<void>}
     */
    async decrypt(encodedString, key) {
        let encryptedString = sodium.from_base64(encodedString);
        if(encryptedString.length < sodium.crypto_secretbox_NONCEBYTES + sodium.crypto_secretbox_MACBYTES) throw new Error('Invalid encrypted text length');

        let nonce      = encryptedString.slice(0, sodium.crypto_secretbox_NONCEBYTES),
            ciphertext = encryptedString.slice(sodium.crypto_secretbox_NONCEBYTES),
            decrypted  = sodium.crypto_secretbox_open_easy(ciphertext, nonce, key);

        return new TextDecoder().decode(decrypted);
    }

    /**
     *
     * @param challengeText
     * @param password
     * @returns {Promise<void>}
     */
    async solveChallenge(challengeText, password) {
        let challenge = sodium.from_base64(challengeText),
            salt      = challenge.slice(0, sodium.crypto_pwhash_SALTBYTES),
            text      = sodium.to_base64(challenge.slice(sodium.crypto_pwhash_SALTBYTES)),
            key       = await this._passwordToKey(password, salt);

        return await this.decrypt(text, key);
    }

    /**
     *
     * @param password
     * @returns {Promise<void>}
     */
    async createChallenge(password) {
        if(password.length < 12) throw new Error('Password is too short');
        if(password.length > 128) throw new Error('Password is too long');

        let challenge          = sodium.to_hex(this._generateRandom(256)),
            salt               = this._generateRandom(sodium.crypto_pwhash_SALTBYTES),
            key                = await this._passwordToKey(password, salt),
            encryptedChallenge = await this.encrypt(challenge, key);

        return {
            challenge: sodium.to_base64(new Uint8Array([...salt, ...sodium.from_base64(encryptedChallenge)])),
            secret   : challenge
        };
    }

    // noinspection JSMethodCanBeStatic
    async _passwordToKey(password, salt) {
        return await sodium.crypto_pwhash(
            sodium.crypto_box_SEEDBYTES,
            password,
            salt,
            sodium.crypto_pwhash_OPSLIMIT_INTERACTIVE,
            sodium.crypto_pwhash_MEMLIMIT_INTERACTIVE,
            sodium.crypto_pwhash_ALG_DEFAULT
        );
    }

    // noinspection JSMethodCanBeStatic
    /**
     *
     * @param length
     * @returns {Uint8Array}
     * @private
     */
    _generateRandom(length) {
        let array = new Uint8Array(length);
        window.crypto.getRandomValues(array);

        return array;
    }

    // noinspection JSMethodCanBeStatic
    /**
     *
     * @param value
     * @param algorithm
     * @returns {Promise<string>}
     */
    async getHash(value, algorithm = 'SHA-1') {
        if(['SHA-1', 'SHA-256', 'SHA-384', 'SHA-512'].indexOf(algorithm) !== -1) {
            let msgBuffer  = new TextEncoder('utf-8').encode(value),
                hashBuffer = await crypto.subtle.digest(algorithm, msgBuffer);
            return sodium.to_hex(new Uint8Array(hashBuffer));
        } else if(algorithm.substr(0, 7) === 'BLAKE2b') {
            let bytes = sodium.crypto_generichash_BYTES_MAX;
            if(algorithm.indexOf('-') !== -1) {
                bytes = algorithm.split('-')[1];
                if(sodium.crypto_generichash_BYTES_MAX < bytes) bytes = sodium.crypto_generichash_BYTES_MAX;
                if(sodium.crypto_generichash_BYTES_MIN > bytes) bytes = sodium.crypto_generichash_BYTES_MIN;
            }

            return sodium.to_hex(await sodium.crypto_generichash(bytes, sodium.from_string(value)));
        } else if(algorithm === 'Argon2') {
            return sodium.crypto_pwhash_str(value, sodium.crypto_pwhash_OPSLIMIT_MIN, sodium.crypto_pwhash_MEMLIMIT_MIN);
        }
    }
}