export default class Encryption {

    constructor() {
    }

    /**
     *
     * @param baseHash
     * @param dataHash
     * @param length
     * @returns {Uint8Array}
     */
    static calculateIv(baseHash, dataHash, length = 16) {
        if(baseHash.length < length) throw "Password too short";
        let iv        = '',
            blocksize = Math.round(baseHash.length / length);

        for(let i = 0; i < length; i++) {
            let char = 0;
            for(let j = 0; j < blocksize; j++) char = baseHash.charCodeAt(char + j);
            while(char > dataHash.length - blocksize) char -= dataHash.length;
            iv += dataHash.charAt(char);
        }

        return new TextEncoder('utf-8').encode(value);
    }

    async getHash(value, algorithm = 'SHA-1') {
        let msgBuffer = new TextEncoder('utf-8').encode(value);
        let hashBuffer = await crypto.subtle.digest(algorithm, msgBuffer);
        let hashArray = Array.from(new Uint8Array(hashBuffer));
        return hashArray.map(b => ('00' + b.toString(16)).slice(-2)).join('');
    }
}