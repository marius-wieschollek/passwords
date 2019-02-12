import API from "@js/Helper/api";

class EncryptionManager {

    /**
     *
     * @param password
     * @param save
     */
    async install(password, save = false, encrypt = false) {
        let hash = await API.getHash(password, 'BLAKE2b-64');
        API.setAccountPassword(hash, 'BLAKE2b-64');
    }
}

let EM = new EncryptionManager();

export default EM;