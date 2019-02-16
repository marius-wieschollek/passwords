import API from "@js/Helper/api";

class EncryptionManager {

    /**
     *
     * @param password
     * @param save
     * @param encrypt
     */
    async install(password, save = false, encrypt = false) {
        API.setAccountChallenge(password);

        if(encrypt !== false) {
            let passwords = await API.listPasswords();

            for(let i=0; i<passwords.length; i++) {
                let password = passwords[i];
                if(!password.shared) API.updatePassword(password)
            }

        }
    }
}

let EM = new EncryptionManager();

export default EM;