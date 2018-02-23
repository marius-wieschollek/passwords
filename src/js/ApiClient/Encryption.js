export default class Encryption {

    constructor() {
        this.fields = {
            password:['url', 'label', 'notes', 'password', 'username'],
            folder:['label'],
            tag: ['label', 'color']
        };
    }

    /**
     * Encrypts an object with the given password
     *
     * @param object
     * @param password
     * @param type
     * @returns {Promise<*>}
     */
    async encryptObject(object, password, type) {
        if(!this.fields.hasOwnProperty(type)) throw "Invalid object type";
        let fields = this.fields[type];

        for(let i =0; i < fields.length; i++) {
            let field = fields[i];

            object[field] = await this.encrypt(object[field], password+field);
        }

        return object;
    }

    /**
     * Decrypts an object with the given password
     *
     * @param object
     * @param password
     * @param type
     * @returns {Promise<*>}
     */
    async decryptObject(object, password, type) {
        if(!this.fields.hasOwnProperty(type)) throw "Invalid object type";
        let fields = this.fields[type];

        for(let i =0; i < fields.length; i++) {
            let field = fields[i];

            object[field] = await this.decrypt(object[field], password+field);
        }

        return object;
    }

    /**
     *
     * @param rawData
     * @param rawPassword
     * @returns {Promise<string>}
     */
    async encrypt(rawData, rawPassword) {
        let data = new TextEncoder().encode(rawData);
        let iv = crypto.getRandomValues(new Uint8Array(16));

        let password = new TextEncoder().encode(rawPassword);
        let passwordHash = await crypto.subtle.digest('SHA-256', password);

        let algorithm = {name: 'AES-CBC', iv: iv};
        let key = await crypto.subtle.importKey('raw', passwordHash, algorithm, false, ['encrypt']);

        let encryptedData = new Uint16Array(await crypto.subtle.encrypt(algorithm, key, data));
        let mergedData = new Uint16Array(await Encryption.hideIv(encryptedData, password, iv));

        return this._utf8ArrayToBase64(new Uint8Array(mergedData.buffer));
    }

    /**
     *
     * @param rawData
     * @param rawPassword
     * @returns {Promise<void>}
     */
    async decrypt(rawData, rawPassword) {
        let password = new TextEncoder().encode(rawPassword);
        let passwordHash = await crypto.subtle.digest('SHA-256', password);

        let encryptedData = new Uint16Array(this._base64ToUtf8Array(rawData).buffer);

        let [data, iv] = await Encryption.extractIv(encryptedData, password, 16);
        let arrayBuffer = new ArrayBuffer(data.length * 2);
        let bufferView = new Uint16Array(arrayBuffer);
        for(let i = 0; i < data.length; i++) bufferView[i] = data[i];

        let algorithm = {name: 'AES-CBC', iv: iv};
        let key = await crypto.subtle.importKey('raw', passwordHash, algorithm, false, ['decrypt']);

        let decryptedData = await crypto.subtle.decrypt(algorithm, key, arrayBuffer);

        return new TextDecoder().decode(decryptedData);
    }

    /**
     *
     * @returns {Uint8Array}
     * @param password
     * @param rawData
     * @param iv
     */
    static async hideIv(rawData, password, iv) {
        let data       = Array.from(rawData),
            dataHash   = new Uint8Array(await crypto.subtle.digest('SHA-512', password)),
            blockSize  = Math.round(dataHash.length / iv.length),
            multiplier = Math.ceil(data.length / 640);

        for(let i = 0; i < iv.length; i++) {
            let start    = i * blockSize,
                position = 0;

            for(let j = 0; j < blockSize; j++) position += dataHash[start + j];
            position = position * multiplier;
            while(position > data.length) position -= data.length;
            data.splice(position, 0, Math.pow(iv[i], 2));
        }

        return data;
    }

    /**
     *
     * @param rawData
     * @param password
     * @param ivLength
     * @returns {Promise<*[]>}
     */
    static async extractIv(rawData, password, ivLength) {
        let data       = Array.from(rawData),
            length     = data.length - ivLength,
            dataHash   = new Uint8Array(await crypto.subtle.digest('SHA-512', password)),
            blockSize  = Math.round(dataHash.length / ivLength),
            multiplier = Math.ceil(length / 640),
            iv         = new Uint8Array(ivLength);

        if(length <= 0) throw "invalid encrypted data";

        for(let i = ivLength - 1; i >= 0; i--) {
            let start    = i * blockSize,
                position = 0;

            for(let j = 0; j < blockSize; j++) position += dataHash[start + j];
            position = position * multiplier;
            while(position > data.length - 1) position -= data.length - 1;
            let entry = data.splice(position, 1);
            iv[i] = Math.sqrt(entry);
        }

        return [data, iv];
    }

    /**
     *
     * @param string
     * @returns {ArrayBuffer}
     */
    static stringToArrayBuffer(string) {
        let arrayBuffer = new ArrayBuffer(string.length * 2);
        let bufferView = new Uint16Array(arrayBuffer);
        for(let i = 0; i < string.length; i++) {
            bufferView[i] = string.charCodeAt(i);
        }
        return arrayBuffer;
    }

    async getHash(value, algorithm = 'SHA-1') {
        let msgBuffer = new TextEncoder('utf-8').encode(value);
        let hashBuffer = await crypto.subtle.digest(algorithm, msgBuffer);
        let hashArray = Array.from(new Uint8Array(hashBuffer));
        return hashArray.map(b => ('00' + b.toString(16)).slice(-2)).join('');
    }

    /**
     *
     * @param buf
     * @returns {*}
     * @private
     */
    _utf8ArrayToBase64(buf) {
        let binstr = Array.prototype.map.call(buf, function(ch) {
            return String.fromCharCode(ch);
        }).join('');
        return btoa(binstr);
    }

    /**
     *
     * @param base64
     * @returns {Uint8Array}
     * @private
     */
    _base64ToUtf8Array(base64) {
        let binstr = atob(base64);
        let buf = new Uint8Array(binstr.length);
        Array.prototype.forEach.call(binstr, function(ch, i) {
            buf[i] = ch.charCodeAt(0);
        });
        return buf;
    }
}