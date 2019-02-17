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
            for(let id in passwords) {
                if(!passwords.hasOwnProperty(id)) continue;
                let password = passwords[id];

                if(!password.shared) {
                    API.updatePassword(password)
                       .then(() => { console.log('Encrypted password ' + password.id);})
                       .catch((e) => { console.error(e);});
                }
            }

            let tags = await API.listTags();
            for(let id in tags) {
                if(!tags.hasOwnProperty(id)) continue;
                let tag = tags[id];

                API.updateTag(tag)
                   .then(() => { console.log('Encrypted tag ' + tag.id);})
                   .catch((e) => { console.error(e);});
            }

            let folders = await API.listFolders();
            for(let id in folders) {
                if(!folders.hasOwnProperty(id)) continue;
                let folder = folders[id];

                API.updateFolder(folder)
                   .then(() => { console.log('Encrypted folder ' + folder.id);})
                   .catch((e) => { console.error(e);});
            }

        }
    }
}

let EM = new EncryptionManager();

export default EM;