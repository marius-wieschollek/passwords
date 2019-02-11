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
    async getHash(value, algorithm = 'SHA-1') {
        if(algorithm === 'SHA-1') {
        let msgBuffer  = new TextEncoder('utf-8').encode(value),
            hashBuffer = await crypto.subtle.digest(algorithm, msgBuffer),
            hashArray  = Array.from(new Uint8Array(hashBuffer));
        return hashArray.map((b) => (`00${b.toString(16)}`).slice(-2)).join('');
        } else {
            let pwd = sodium.crypto_pwhash_str(value, sodium.crypto_pwhash_OPSLIMIT_MIN, sodium.crypto_pwhash_MEMLIMIT_MIN);
            console.log(pwd, sodium.crypto_pwhash_str_verify(pwd, value));
        }
    }
}