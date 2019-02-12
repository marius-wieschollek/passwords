import sodium from "libsodium-wrappers";

export default class Encryption {

    set key(key) {
        this._key = sodium.crypto_generichash(32, key);
    }

    constructor() {
        this.fields = {
            password: ['url', 'label', 'notes', 'password', 'username', 'customFields'],
            folder  : ['label'],
            tag     : ['label', 'color']
        };
        this._key = null;
        this.ready();
    }

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
     * @returns {Promise<string>}
     */
    async encrypt(message) {
        let nonce     = crypto.getRandomValues(new Uint8Array(sodium.crypto_secretbox_NONCEBYTES)),
            encrypted = new Uint8Array([...nonce, ...sodium.crypto_secretbox_easy(message, nonce, this._key)]);

        return sodium.to_base64(encrypted);
    }

    /**
     * @param encodedString
     *
     * @returns {Promise<void>}
     */
    async decrypt(encodedString) {
        let encryptedString = sodium.from_base64(encodedString);
        if(encryptedString.length < sodium.crypto_secretbox_NONCEBYTES + sodium.crypto_secretbox_MACBYTES) throw new Error('Invalid encrypted text length');

        let nonce      = encryptedString.slice(0, sodium.crypto_secretbox_NONCEBYTES),
            ciphertext = encryptedString.slice(sodium.crypto_secretbox_NONCEBYTES),
            decrypted  = sodium.crypto_secretbox_open_easy(ciphertext, nonce, this._key);

        return new TextDecoder().decode(decrypted);
    }

    /**
     *
     * @param value
     * @param algorithm
     * @returns {Promise<string>}
     */
    static async getHash(value, algorithm = 'SHA-1') {
        if(['SHA-1', 'SHA-256', 'SHA-384', 'SHA-512'].indexOf(algorithm)  !== -1) {
            let msgBuffer  = new TextEncoder('utf-8').encode(value),
                hashBuffer = await crypto.subtle.digest(algorithm, msgBuffer);
            return sodium.to_hex(new Uint8Array(hashBuffer));
        } else if(algorithm.substr(0,7) === 'BLAKE2b') {
            let bytes = sodium.crypto_generichash_BYTES_MAX;
            if(algorithm.indexOf('-') !== -1) {
                let bytes = algorithm.split('-')[1];
                if(sodium.crypto_generichash_BYTES_MAX < bytes) bytes = sodium.crypto_generichash_BYTES_MAX;
                if(sodium.crypto_generichash_BYTES_MIN > bytes) bytes = sodium.crypto_generichash_BYTES_MIN;
            }

            return sodium.to_hex(await sodium.crypto_generichash(bytes, sodium.from_string(value)));
        } else if(algorithm === 'Argon2') {
            return sodium.crypto_pwhash_str(value, sodium.crypto_pwhash_OPSLIMIT_MIN, sodium.crypto_pwhash_MEMLIMIT_MIN);
        }
    }
}