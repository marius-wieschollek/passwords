import API from "@js/Helper/api";

class EncryptionManager {

    /**
     *
     * @param password
     * @param save
     */
    async install(password, save = false, encrypt = false) {
        API.setAccountChallenge(password);
    }
}

let EM = new EncryptionManager();

export default EM;