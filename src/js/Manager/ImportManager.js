import API from '@js/Helper/api';

/**
 *
 */
class ImportManager {

    constructor() {
        this.defaultFolder = '00000000-0000-0000-0000-000000000000';
        this.progress = () => {};
        this.processed = 0;
        this.total = 0;
    }

    /**
     *
     * @param data
     * @param type
     * @param mode
     * @param progress
     * @returns {Promise<void>}
     */
    async importDatabase(data, type = 'json', mode = 0, progress = () => {}) {
        mode = Number.parseInt(mode);
        this.total = 1;
        this.processed = 0;
        this.progress = progress;
        this.countProgress('Parsing input file');

        switch(type) {
            case 'json':
                data = JSON.parse(data);
                break;
            default:
                throw "Invalid import type: " + type;
        }

        this.total = 0;
        for(let k in data) {
            if(!data.hasOwnProperty(k) || !Array.isArray(data[k])) continue;
            this.total += data[k].length;
        }

        let tagMapping = {};
        if(data.tags) {
            this.countProgress('Importing tags');
            tagMapping = await this.importTags(data.tags, mode);
        }

        let folderMapping = {};
        if(data.folders) {
            this.countProgress('Importing folders');
            folderMapping = await this.importFolders(data.folders, mode);
        }

        let passwordMapping = {};
        if(data.passwords) {
            if(!data.hasOwnProperty('tags')) tagMapping = await this.getTagMapping();
            if(!data.hasOwnProperty('folders')) folderMapping = await this.getFolderMapping();
            this.countProgress('Importing passwords');

            passwordMapping = this.importPasswords(data.passwords, mode, tagMapping, folderMapping);
        }
    }

    /**
     *
     * @returns {Promise<{}>}
     */
    async getTagMapping() {
        this.countProgress('Analyzing database');

        let tags  = await API.listTags(),
            idMap = {};

        for(let k in tags) {
            if(!tags.hasOwnProperty(k)) continue;
            idMap[k] = k;
        }

        return idMap;
    }

    /**
     *
     * @returns {Promise<{string: string}>}
     */
    async getFolderMapping() {
        this.countProgress('Analyzing database');

        let folders = await API.listFolders(),
            idMap   = {'00000000-0000-0000-0000-000000000000': this.defaultFolder};

        for(let k in folders) {
            if(!folders.hasOwnProperty(k)) continue;
            idMap[k] = k;
        }

        return idMap;
    }

    /**
     *
     * @param tags
     * @param mode
     * @returns {Promise<{}>}
     */
    async importTags(tags, mode = 0) {
        let db    = await API.listTags(),
            idMap = {};

        for(let k in db) {
            if(!db.hasOwnProperty(k)) continue;
            idMap[k] = k;
        }

        for(let i = 0; i < tags.length; i++) {
            let tag = tags[i];

            if(mode !== 3 && tag.hasOwnProperty('id') && db.hasOwnProperty(tag.id)) {
                if(mode === 1 || (mode === 0 && db[tag.id].revision === tag.revision)) {
                    this.countProgress();
                    continue;
                }

                idMap[tag.id] = tag.id;
                await API.updateTag(tag);
            } else {
                let info = await API.createTag(tag);
                idMap[tag.id] = info.id;
            }

            this.countProgress();
        }

        return idMap;
    }

    /**
     *
     * @param folders
     * @param mode
     * @returns {Promise<{}>}
     */
    async importFolders(folders, mode = 0) {
        let db    = await API.listFolders(),
            idMap = {'00000000-0000-0000-0000-000000000000': this.defaultFolder};

        for(let k in db) {
            if(!db.hasOwnProperty(k)) continue;
            idMap[k] = k;
        }

        folders.sort((a, b) => {
            if(a.parent === null || b.parent === null) return 0;
            if(a.id === null || b.id === null) return 0;
            if(a.parent === b.id) return 1;
            if(b.parent === a.id) return -1;
            return 0;
        });

        for(let i = 0; i < folders.length; i++) {
            let folder = folders[i];

            if(folder.id === this.defaultFolder) {
                this.countProgress();
                continue;
            }

            if(idMap.hasOwnProperty(folder.parent)) {
                folder.parent = idMap[folder.parent];
            } else {
                folder.parent = this.defaultFolder;
            }

            if(folder.parent === folder.id) {
                folder.parent = this.defaultFolder;
            }

            if(mode !== 3 && folder.hasOwnProperty('id') && db.hasOwnProperty(folder.id)) {
                if(mode === 1 || (mode === 0 && db[folder.id].revision === folder.revision)) {
                    this.countProgress();
                    continue;
                }

                idMap[folder.id] = folder.id;
                await API.updateFolder(folder);
            } else {
                let info = await API.createFolder(folder);
                idMap[folder.id] = info.id;
            }

            this.countProgress();
        }

        return idMap;
    }

    /**
     *
     * @param passwords
     * @param mode
     * @param tagMapping
     * @param folderMapping
     * @returns {Promise<{}>}
     */
    async importPasswords(passwords, mode = 0, tagMapping = {}, folderMapping = {}) {
        let db    = await API.listPasswords(),
            idMap = {};

        for(let k in db) {
            if(!db.hasOwnProperty(k)) continue;
            idMap[k] = k;
        }

        for(let i = 0; i < passwords.length; i++) {
            let password = passwords[i];

            if(password.tags) {
                let tags = [];
                for(let j = 0; j < password.tags.length; j++) {
                    let id = password.tags[j];

                    if(tagMapping.hasOwnProperty(id)) {
                        tags.push(tagMapping[id])
                    }
                }
                password.tags = tags;
            }

            if(folderMapping.hasOwnProperty(password.folder)) {
                password.folder = folderMapping[password.folder];
            } else {
                password.folder = this.defaultFolder;
            }

            if(mode !== 3 && password.hasOwnProperty('id') && db.hasOwnProperty(password.id)) {
                if(mode === 1 || (mode === 0 && db[password.id].revision === password.revision)) {
                    this.countProgress();
                    continue;
                }

                idMap[password.id] = password.id;
                await API.updatePassword(password);
            } else {
                let info = await API.createPassword(password);
                idMap[info.id] = info.id;
            }

            this.countProgress();
        }

        return idMap;
    }

    countProgress(status = null) {
        if(status === null) this.processed++;
        this.progress(this.processed, this.total, status);
    }
}

let IM = new ImportManager();

export default IM;