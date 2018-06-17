class EncryptionManager {

    /**
     *
     * @param password
     * @param save
     */
    setPassword(password, save = false) {
        console.log(password, save);
    }
}

let EM = new EncryptionManager();

export default EM;