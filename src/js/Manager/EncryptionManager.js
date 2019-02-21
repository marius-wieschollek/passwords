import API from "@js/Helper/api";
import Utility from "@/js/Classes/Utility";
import Localisation from "@/js/Classes/Localisation";

class EncryptionManager {

    /**
     *
     * @param password
     * @param save
     * @param encrypt
     */
    async install(password, save = false, encrypt = false) {
        await API.setAccountChallenge(password);

        if(encrypt !== false) {
            let tagMap    = {},
                folderMap = {};

            await Promise.all(
                [
                    this._encryptTags(tagMap),
                    this._encryptFolders(folderMap)
                ]
            );

            await this._encryptPasswords(folderMap, tagMap);

            await Promise.all(
                [
                    this._deleteObjects(tagMap, 'tag'),
                    this._deleteObjects(folderMap, 'folder')
                ]
            );
        }

        if(save) {
            let username = document.querySelector('meta[name=api-user]').getAttribute('content'),
                label    = Localisation.translate('Passwords App Master Password'),
                url      = location.href;

            API.createPassword({username, password, label, url});
        }
    }

    /**
     *
     * @param folderMap
     * @param tagMap
     * @returns {Promise<void>}
     * @private
     */
    async _encryptPasswords(folderMap, tagMap) {
        let passwords = await API.listPasswords('model+tags'),
            queue     = [];

        for(let id in passwords) {
            if(!passwords.hasOwnProperty(id)) continue;
            let password = passwords[id];

            queue.push(this._encryptPassword(password, folderMap, tagMap));

            if(queue.length > 10) {
                await Promise.all(queue);
                queue = [];
            }
        }

        if(queue.length !== null) await Promise.all(queue);
    }

    /**
     *
     * @param password
     * @param folderMap
     * @param tagMap
     * @returns {Promise<void>}
     * @private
     */
    async _encryptPassword(password, folderMap, tagMap) {
        password.folder = folderMap[password.folder];

        let tags = [];
        for(let id in password.tags) {
            if(!password.tags.hasOwnProperty(id)) continue;
            tags.push(tagMap[id]);
        }
        password.tags = tags;

        if(!password.shared) {
            await this._deleteObject(password.id, 'password');

            try {
                await API.createPassword(password);
                console.log(`Encrypted password ${password.id}`);
            } catch(e) {
                console.error(e);
                throw e;
            }
        } else {
            try {
                await API.updatePassword(password);
                console.log(`Updated password ${password.id}`);
            } catch(e) {
                console.error(e);
                throw e;
            }
        }
    }

    /**
     *
     * @returns {Promise<void>}
     * @private
     */
    async _encryptTags(idMap) {
        let tags  = await API.listTags(),
            queue = [];

        for(let id in tags) {
            if(!tags.hasOwnProperty(id)) continue;

            queue.push(this._encryptTag(tags[id], idMap));

            if(queue.length > 10) {
                await Promise.all(queue);
                queue = [];
            }
        }

        if(queue.length !== null) await Promise.all(queue);

        return idMap;
    }

    /**
     *
     * @param tag
     * @param idMap
     * @returns {Promise<void>}
     * @private
     */
    async _encryptTag(tag, idMap) {
        try {
            let result = await API.createTag(tag);
            idMap[tag.id] = result.id;

            console.log(`Encrypted tag ${tag.id}`);
        } catch(e) {
            console.error(e);
            throw e;
        }
    }

    /**
     *
     * @param idMap
     * @returns {Promise<*>}
     * @private
     */
    async _encryptFolders(idMap) {
        let folders = await API.listFolders(),
        queue = [];

        idMap['00000000-0000-0000-0000-000000000000'] = '00000000-0000-0000-0000-000000000000';
        folders = this._sortFoldersForUpgrade(folders);

        for(let id in folders) {
            if(!folders.hasOwnProperty(id)) continue;
            let folder = folders[id];

            if(!idMap.hasOwnProperty(folder.parent) || queue.length > 10) {
                await Promise.all(queue);
                queue = [];
            }

            queue.push(this._encryptFolder(folder, idMap));
        }

        if(queue.length !== null) await Promise.all(queue);

        return idMap;
    }

    /**
     *
     * @param folder
     * @param idMap
     * @returns {Promise<void>}
     * @private
     */
    async _encryptFolder(folder, idMap) {

        try {
            folder.parent = idMap[folder.parent];
            let result = await API.createFolder(folder);
            idMap[folder.id] = result.id;

            console.log(`Encrypted folder ${folder.id}`);
        } catch(e) {
            console.error(e);
            throw e;
        }
    }

    /**
     *
     * @param folderDb
     * @returns {Array}
     * @private
     */
    _sortFoldersForUpgrade(folderDb) {
        let folders = [],
            sortLog = ['00000000-0000-0000-0000-000000000000'];

        folderDb = Utility.objectToArray(folderDb);
        while(folderDb.length !== 0) {
            for(let i = 0; i < folderDb.length; i++) {
                let folder = folderDb[i];

                if(sortLog.indexOf(folder.parent) !== -1) {
                    sortLog.push(folder.id);
                    folders.push(folder);
                    folderDb.splice(i, 1);
                    i--;
                }
            }
        }

        return folders;
    }

    /**
     *
     * @param idMap
     * @param type
     * @returns {Promise<void>}
     * @private
     */
    async _deleteObjects(idMap, type) {
        let queue = [];

        for(let id in idMap) {
            if(idMap.hasOwnProperty(id) && id !== '00000000-0000-0000-0000-000000000000') {
                queue.push(this._deleteObject(id, type));
            }
        }

        await Promise.all(queue);
    }

    /**
     *
     * @param id
     * @param type
     * @returns {Promise<void>}
     * @private
     */
    async _deleteObject(id, type) {
        let deleteFunc = 'delete' + type.capitalize();

        try {
            await API[deleteFunc](id);
            await API[deleteFunc](id);
        } catch(e) {
            console.error(e);
        }
    }
}

let EM = new EncryptionManager();

export default EM;