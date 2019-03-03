import API from "@js/Helper/api";
import Utility from '@js/Classes/Utility';
import Localisation from '@js/Classes/Localisation';

class EncryptionManager {

    constructor() {
        this._statusFunc = null;
        this.status = null;
    }

    /**
     *
     * @param password
     * @param save
     * @param encrypt
     * @param statusFunc
     */
    async install(password, save = false, encrypt = false, statusFunc = null) {
        this._resetStatus(statusFunc);
        await this._updateKeychain(password);

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
            await this._cleanDatabase(tagMap, folderMap);
        }

        if(save) await this._saveMasterPassword(password);
    }

    /**
     *
     * @param password
     * @returns {Promise<void>}
     * @private
     */
    async _updateKeychain(password) {
        this._sendStatus('keychain', 'processing', 1);
        try {
            await API.setAccountChallenge(password);
            this._sendStatus('keychain', 'done');
        } catch(e) {
            this._sendStatus('keychain', 'error', e);
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
        this._sendStatus('passwords', 'loading');
        let passwords = await API.listPasswords('model+tags'),
            queue     = [];

        this._sendStatus('passwords', 'processing', Object.keys(passwords).length);
        for(let id in passwords) {
            if(!passwords.hasOwnProperty(id)) continue;
            let password = passwords[id];

            queue.push(this._encryptPassword(password, folderMap, tagMap));

            if(queue.length > 15) {
                await Promise.all(queue);
                queue = [];
            }
        }

        if(queue.length !== 0) await Promise.all(queue);
        this._sendStatus('passwords', 'done');
    }

    /**
     *
     * @param password
     * @param folderMap
     * @param tagMap
     * @param current
     * @param total
     * @returns {Promise<void>}
     * @private
     */
    async _encryptPassword(password, folderMap, tagMap, current, total) {
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
                this._sendStatus('passwords');
            } catch(e) {
                this._sendStatus('passwords', 'error', e);
                throw e;
            }
        } else {
            try {
                await API.updatePassword(password);
                console.log(`Updated password ${password.id}`);
                this._sendStatus('passwords');
            } catch(e) {
                this._sendStatus('passwords', 'error', e);
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
        this._sendStatus('tags', 'loading');
        let tags  = await API.listTags(),
            queue = [];

        this._sendStatus('tags', 'processing', Object.keys(tags).length);
        for(let id in tags) {
            if(!tags.hasOwnProperty(id)) continue;

            queue.push(this._encryptTag(tags[id], idMap));

            if(queue.length > 10) {
                await Promise.all(queue);
                queue = [];
            }
        }

        if(queue.length !== 0) await Promise.all(queue);

        this._sendStatus('tags', 'done');
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

            this._sendStatus('tags');
            console.log(`Encrypted tag ${tag.id}`);
        } catch(e) {
            this._sendStatus('tags', 'error', e);
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
        this._sendStatus('folders', 'loading');
        let folders = await API.listFolders(),
            queue   = [];

        idMap['00000000-0000-0000-0000-000000000000'] = '00000000-0000-0000-0000-000000000000';
        folders = this._sortFoldersForUpgrade(folders);

        this._sendStatus('folders', 'processing', Object.keys(folders).length);
        for(let id in folders) {
            if(!folders.hasOwnProperty(id)) continue;
            let folder = folders[id];

            if(!idMap.hasOwnProperty(folder.parent) || queue.length > 10) {
                await Promise.all(queue);
                queue = [];
            }

            queue.push(this._encryptFolder(folder, idMap));
        }

        await Promise.all(queue);

        this._sendStatus('folders', 'done');
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

            this._sendStatus('folders');
            console.log(`Encrypted folder ${folder.id}`);
        } catch(e) {
            this._sendStatus('folders', 'error', e);
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
     * @param tagMap
     * @param folderMap
     * @returns {Promise<void>}
     * @private
     */
    async _cleanDatabase(tagMap, folderMap) {
        let total = Object.keys(tagMap).length + Object.keys(folderMap).length;

        this._sendStatus('cleanup', 'processing', total);
        await Promise.all(
            [
                this._deleteObjects(tagMap, 'tag'),
                this._deleteObjects(folderMap, 'folder')
            ]
        );
        this._sendStatus('cleanup', 'done');
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

                if(queue.length > 10) {
                    await Promise.all(queue);
                    queue = [];
                }
            }
        }

        if(queue.length !== 0) await Promise.all(queue);
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
            if(type === 'tag' || e.status && e.status !== 404) {
                this._sendStatus('cleanup', 'error', e);
            }
        }

        if(type !== 'password') this._sendStatus('cleanup', 'processing');
    }

    /**
     *
     * @param password
     * @returns {Promise<void>}
     * @private
     */
    async _saveMasterPassword(password) {
        this._sendStatus('password', 'processing', 1);
        let username = document.querySelector('meta[name=api-user]').getAttribute('content'),
            label    = Localisation.translate('Passwords App Master Password'),
            url      = location.href;

        try {
            await API.createPassword({username, password, label, url});
            this._sendStatus('password', 'done');
        } catch(e) {
            this._sendStatus('password', 'error', e);
        }
    }

    /**
     *
     * @param statusFunc
     * @private
     */
    _resetStatus(statusFunc) {
        this._statusFunc = statusFunc;
        this.status = {
            passwords: {status: 'waiting', total: 0, current: 0, errors: []},
            keychain : {status: 'waiting', total: 0, current: 0, errors: []},
            folders  : {status: 'waiting', total: 0, current: 0, errors: []},
            cleanup  : {status: 'waiting', total: 0, current: 0, errors: []},
            tags     : {status: 'waiting', total: 0, current: 0, errors: []},
            save     : {status: 'waiting', total: 0, current: 0, errors: []}
        };
    }

    /**
     *
     * @param section
     * @param status
     * @param data
     * @private
     */
    _sendStatus(section, status = 'processing', data = null) {
        if(this._statusFunc === null || !this.status.hasOwnProperty(section)) return;
        let object = this.status[section];

        if(status === 'processing') {
            object.status = 'processing';
            if(data !== null) {
                object.total = data;
                object.current = 0;
            } else {
                object.current++;
            }
        } else if(status === 'loading') {
            object.status = 'loading';
        } else if(status === 'done') {
            object.status = object.errors.length === 0 ? 'success':'failed';
            if(object.status === 'success') object.current = object.total;
        } else if(status === 'error') {
            console.error(data);
            object.errors.push(data);
            if(section !== cleanup) object.status = 'failed';
        }

        this._statusFunc(this.status);
    }
}

let EM = new EncryptionManager();

export default EM;