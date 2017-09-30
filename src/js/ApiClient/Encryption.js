export default class Encryption {

    constructor() {
    }

    async getHash(value, algorithm = 'SHA-1') {
        const msgBuffer = new TextEncoder('utf-8').encode(value);
        const hashBuffer = await crypto.subtle.digest(algorithm, msgBuffer);
        const hashArray = Array.from(new Uint8Array(hashBuffer));
        return hashArray.map(b => ('00' + b.toString(16)).slice(-2)).join('');
    }
}