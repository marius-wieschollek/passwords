import sodium from "libsodium-wrappers";

export default class Encryption {

    constructor() {
        this.fields = {
            password: ['url', 'label', 'notes', 'password', 'username', 'customFields'],
            folder  : ['label'],
            tag     : ['label', 'color']
        };
        this.key = null;
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
            object[field] = await this.encrypt(data, this.key);
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
            object[field] = await this.decrypt(data, this.key);
        }

        return object;
    }

    /**
     * @param message
     * @param key
     *
     * @returns {Promise<string>}
     */
    async encrypt(message, key) {
        let nonce     = crypto.getRandomValues(new Uint8Array(sodium.crypto_secretbox_NONCEBYTES)),
            encrypted = new Uint8Array([...nonce, ...sodium.crypto_secretbox_easy(message, nonce, key)]);

        return sodium.to_base64(encrypted);
    }

    /**
     * @param encodedString
     * @param key
     *
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

    createEncryptionKey() {
        this.key = crypto.getRandomValues(
            new Uint8Array(sodium.crypto_secretbox_KEYBYTES)
        );
    }

    setEncryptionKey(key) {
        this.key = sodium.from_base64(key);
    }

    getEncryptionKey(key) {
        return sodium.to_base64(this.key);
    }

    /**
     *
     * @param value
     * @param algorithm
     * @returns {Promise<string>}
     */
    async getHash(value, algorithm = 'SHA-1') {
        let msgBuffer  = new TextEncoder('utf-8').encode(value),
            hashBuffer = await crypto.subtle.digest(algorithm, msgBuffer),
            hashArray  = Array.from(new Uint8Array(hashBuffer));
        return hashArray.map((b) => (`00${b.toString(16)}`).slice(-2)).join('');
    }
}