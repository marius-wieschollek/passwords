export default class Encryption {

    constructor() {
        this.fields = {
            password: ['url', 'label', 'notes', 'password', 'username'],
            folder  : ['label'],
            tag     : ['label', 'color']
        };
        this.base64 = new Base64();
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
        if(!this.fields.hasOwnProperty(type)) throw new Error('Invalid object type');
        let fields = this.fields[type];

        for(let i = 0; i < fields.length; i++) {
            let field = fields[i],
                data  = object[field];

            if(data.length === 0) continue;
            object[field] = await this.encrypt(data, password + field);
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
        if(!this.fields.hasOwnProperty(type)) throw new Error('Invalid object type');
        let fields = this.fields[type];

        for(let i = 0; i < fields.length; i++) {
            let field = fields[i],
                data  = object[field];

            if(data.length === 0) continue;
            object[field] = await this.decrypt(data, password + field);
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
        let data = new TextEncoder().encode(rawData),
            iv = crypto.getRandomValues(new Uint8Array(16)),
            password = new TextEncoder().encode(rawPassword),
            passwordHash = await crypto.subtle.digest('SHA-256', password),
            algorithm = {name: 'AES-CBC', iv},
            key = await crypto.subtle.importKey('raw', passwordHash, algorithm, false, ['encrypt']),
            encryptedData = new Uint16Array(await crypto.subtle.encrypt(algorithm, key, data)),
            mergedData = new Uint8Array(await this.hideIv(new Uint8Array(encryptedData.buffer), rawPassword, iv));

        return this._utf8ArrayToBase64(mergedData);
    }

    /**
     *
     * @param rawData
     * @param rawPassword
     * @returns {Promise<void>}
     */
    async decrypt(rawData, rawPassword) {
        let password = new TextEncoder().encode(rawPassword),
            passwordHash = await crypto.subtle.digest('SHA-256', password),
            encryptedData = this._base64ToUtf8Array(rawData),
            [data, iv] = await this.extractIv(encryptedData, rawPassword, 16),
            utf16array = new Uint16Array(new Uint8Array(data).buffer),
            algorithm = {name: 'AES-CBC', iv},
            key = await crypto.subtle.importKey('raw', passwordHash, algorithm, false, ['decrypt']),
            decryptedData = await crypto.subtle.decrypt(algorithm, key, utf16array.buffer);

        return new TextDecoder().decode(decryptedData);
    }

    /**
     *
     * @returns {Uint8Array}
     * @param password
     * @param rawData
     * @param iv
     */
    async hideIv(rawData, password, iv) {
        let data       = Array.from(rawData),
            dataHash   = Encryption.stringToUint8Array(await this.getHash(password, 'SHA-512')),
            blockSize  = Math.round(dataHash.length / iv.length),
            multiplier = Math.ceil(data.length / 640);

        for(let i = 0; i < iv.length; i++) {
            let start    = i * blockSize,
                position = 0;

            for(let j = 0; j < blockSize; j++) position += dataHash[start + j];
            position *= multiplier;
            while(position > data.length) position -= data.length;
            data.splice(position, 0, iv[i]);
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
    async extractIv(rawData, password, ivLength) {
        let data       = Array.from(rawData),
            length     = data.length - ivLength,
            dataHash   = Encryption.stringToUint8Array(await this.getHash(password, 'SHA-512')),
            blockSize  = Math.round(dataHash.length / ivLength),
            multiplier = Math.ceil(length / 640),
            iv         = new Uint8Array(ivLength);

        if(length <= 0) throw new Error('Invalid encrypted data');

        for(let i = ivLength - 1; i >= 0; i--) {
            let start    = i * blockSize,
                position = 0;

            for(let j = 0; j < blockSize; j++) position += dataHash[start + j];
            position *= multiplier;
            while(position > data.length - 1) position -= data.length - 1;
            iv[i] =data.splice(position, 1);
        }

        return [data, iv];
    }

    /**
     *
     * @param string
     * @returns {ArrayBuffer}
     */
    static stringToUint8Array(string) {
        let arrayBuffer = new ArrayBuffer(string.length),
            bufferView = new Uint8Array(arrayBuffer);
        for(let i = 0; i < string.length; i++) {
            bufferView[i] = string.charCodeAt(i);
        }
        return new Uint8Array(arrayBuffer);
    }

    /**
     *
     * @param value
     * @param algorithm
     * @returns {Promise<string>}
     */
    async getHash(value, algorithm = 'SHA-1') {
        let msgBuffer = new TextEncoder('utf-8').encode(value),
            hashBuffer = await crypto.subtle.digest(algorithm, msgBuffer),
            hashArray = Array.from(new Uint8Array(hashBuffer));
        return hashArray.map((b) => (`00${b.toString(16)}`).slice(-2)).join('');
    }

    /**
     *
     * @param buffer
     * @returns {*}
     * @private
     */
    _utf8ArrayToBase64(buffer) {
        let binary = Array.prototype.map.call(buffer, (ch) => {return String.fromCharCode(ch);}).join('');
        return this.base64.encode(binary);
    }

    /**
     *
     * @param base64
     * @returns {Uint8Array}
     * @private
     */
    _base64ToUtf8Array(base64) {
        let binary = this.base64.decode(base64),
            buffer = new Uint8Array(binary.length);
        Array.prototype.forEach.call(binary, (ch, i) => {buffer[i] = ch.charCodeAt(0);});
        return buffer;
    }
}

/**
 * This base64 encoder solves the utf8 issues of the browser built-in utf8 encoder
 * @see http://www.webtoolkit.info/javascript_base64.html
 */
class Base64 {

    constructor() {
        this._keyStr = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=';
    }

    /**
     *
     * @param input
     * @returns {string}
     */
    encode(input) {
        input = Base64._utf8Encode(input);

        let output = '', i = 0;
        while(i < input.length) {
            let chr1 = input.charCodeAt(i++),
                chr2 = input.charCodeAt(i++),
                chr3 = input.charCodeAt(i++),
                enc1 = chr1 >> 2,
                enc2 = ((chr1 & 3) << 4) | (chr2 >> 4),
                enc3 = ((chr2 & 15) << 2) | (chr3 >> 6),
                enc4 = chr3 & 63;

            if(isNaN(chr2)) {
                enc3 = enc4 = 64;
            } else if(isNaN(chr3)) {
                enc4 = 64;
            }

            output += this._keyStr.charAt(enc1) + this._keyStr.charAt(enc2) +
                      this._keyStr.charAt(enc3) + this._keyStr.charAt(enc4);
        }

        return output;
    }

    /**
     *
     * @param input
     * @returns {*}
     */
    decode(input) {
        input = input.replace(/[^A-Za-z0-9+\/=]/g, '');

        let output = '', i = 0;
        while(i < input.length) {
            let enc1 = this._keyStr.indexOf(input.charAt(i++)),
                enc2 = this._keyStr.indexOf(input.charAt(i++)),
                enc3 = this._keyStr.indexOf(input.charAt(i++)),
                enc4 = this._keyStr.indexOf(input.charAt(i++)),
                chr1 = (enc1 << 2) | (enc2 >> 4),
                chr2 = ((enc2 & 15) << 4) | (enc3 >> 2),
                chr3 = ((enc3 & 3) << 6) | enc4;

            output += String.fromCharCode(chr1);
            if(enc3 !== 64) output += String.fromCharCode(chr2);
            if(enc4 !== 64) output += String.fromCharCode(chr3);
        }

        return Base64._utf8Decode(output);
    }

    /**
     *
     * @param string
     * @returns {string}
     * @private
     */
    static _utf8Encode(string) {
        string = string.replace(/\r\n/g, '\n');

        let utf8text = '';
        for(let i = 0; i < string.length; i++) {
            let charCode = string.charCodeAt(i);

            if(charCode < 128) {
                utf8text += String.fromCharCode(charCode);
            } else if((charCode > 127) && (charCode < 2048)) {
                utf8text += String.fromCharCode((charCode >> 6) | 192);
                utf8text += String.fromCharCode((charCode & 63) | 128);
            } else {
                utf8text += String.fromCharCode((charCode >> 12) | 224);
                utf8text += String.fromCharCode(((charCode >> 6) & 63) | 128);
                utf8text += String.fromCharCode((charCode & 63) | 128);
            }

        }

        return utf8text;
    }

    /**
     *
     * @param utf8text
     * @returns {string}
     * @private
     */
    static _utf8Decode(utf8text) {
        let string = '', i = 0;

        while(i < utf8text.length) {
            let charCode = utf8text.charCodeAt(i);

            if(charCode < 128) {
                string += String.fromCharCode(charCode);
                i++;
            } else if((charCode > 191) && (charCode < 224)) {
                let c2 = utf8text.charCodeAt(i + 1);
                string += String.fromCharCode(((charCode & 31) << 6) | (c2 & 63));
                i += 2;
            } else {
                let c2 = utf8text.charCodeAt(i + 1),
                    c3 = utf8text.charCodeAt(i + 2);
                string += String.fromCharCode(((charCode & 15) << 12) | ((c2 & 63) << 6) | (c3 & 63));
                i += 3;
            }
        }

        return string;
    }
}