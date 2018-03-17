import API from '@js/Helper/api';
import Localisation from '@js/Classes/Localisation';
import ImportCsvConversionHelper from '@js/Helper/Import/CsvConversionHelper';
import ImportJsonConversionHelper from '@js/Helper/Import/JsonConversionHelper';

/**
 *
 */
export class ImportManager {

    constructor() {
        this.defaultFolder = '00000000-0000-0000-0000-000000000000';
        this.progress = () => {};
        this.processed = 0;
        this.total = 0;
        this.errors = [];
    }

    /**
     *
     * @param data
     * @param type
     * @param options
     * @param progress
     * @returns {Promise<boolean>}
     */
    async importDatabase(data, type = 'json', options = {}, progress = () => {}) {
        options.mode = Number.parseInt(options.mode);
        this.errors = [];
        this.total = 1;
        this.processed = 0;
        this.progress = progress;
        this.countProgress('Parsing input file');

        switch(type) {
            case 'json':
                data = await ImportJsonConversionHelper.processBackupJson(data, options);
                break;
            case 'pmanJson':
                data = await ImportJsonConversionHelper.processPassmanJson(data);
                break;
            case 'pmanCsv':
                data = await ImportCsvConversionHelper.processPassmanCsv(data);
                break;
            case 'csv':
                data = await ImportCsvConversionHelper.processGenericCsv(data, options);
                break;
            default:
                throw new Error(`Invalid import type: ${type}`);
        }

        this.total = 0;
        for(let k in data) {
            if(!data.hasOwnProperty(k) || !Array.isArray(data[k])) continue;
            this.total += data[k].length;
        }

        let tagMapping    = {},
            folderMapping = {};

        try {
            if(data.tags) tagMapping = await this.importTags(data.tags, options.mode);
        } catch(e) {
            console.error(e);
            throw new Error('Unable to create tags');
        }

        try {
            if(data.folders) folderMapping = await this.importFolders(data.folders, options.mode);
        } catch(e) {
            console.error(e);
            throw new Error('Unable to create folders');
        }

        if(data.passwords) {
            if(!data.hasOwnProperty('tags')) tagMapping = await this.getTagMapping();
            if(!data.hasOwnProperty('folders')) folderMapping = await this.getFolderMapping();

            try {
                await this.importPasswords(data.passwords, options.mode, options.skipShared, tagMapping, folderMapping);
            } catch(e) {
                console.error(e);
                throw new Error('Unable to create passwords');
            }
        }

        return this.errors;
    }

    /**
     *
     * @returns {Promise<{}>}
     */
    async getTagMapping() {
        this.countProgress('Analyzing tags');

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
        this.countProgress('Analyzing folders');

        let folders = await API.listFolders(),
            idMap   = {};

        idMap[this.defaultFolder] = this.defaultFolder;
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
        this.countProgress('Reading tags');
        let db    = await API.listTags(),
            idMap = {};

        for(let k in db) {
            if(!db.hasOwnProperty(k)) continue;
            idMap[k] = k;
        }

        this.countProgress('Importing tags');
        for(let i = 0; i < tags.length; i++) {
            let tag = tags[i];

            try {
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
            } catch(e) {
                console.error(e, tag);
                this.errors.push(Localisation.translate('"{error}" in tag "{label}".', {label: tag.label, error: e.message}));
            }

            this.countProgress();
        }

        return idMap;
    }

    /**
     *
     * @param folderDb
     * @param mode
     * @returns {Promise<{}>}
     */
    async importFolders(folderDb, mode = 0) {
        this.countProgress('Reading folders');
        let db    = await API.listFolders(),
            idMap = {};


        idMap[this.defaultFolder] = this.defaultFolder;
        for(let k in db) {
            if(!db.hasOwnProperty(k)) continue;
            idMap[k] = k;
        }
        let folders = ImportManager._sortFoldersForImport(folderDb, idMap);

        this.countProgress('Importing folders');
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

            try {
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
            } catch(e) {
                console.error(e, folder);
                this.errors.push(Localisation.translate('"{error}" in folder "{label}".', {label: folder.label, error: e.message}));
            }

            this.countProgress();
        }

        return idMap;
    }

    /**
     *
     * @param folderDb
     * @param idMap
     * @returns {Array}
     * @private
     */
    static _sortFoldersForImport(folderDb, idMap) {
        let lastLength = folderDb.length,
            folders    = [],
            sortLog    = [];

        while(folderDb.length !== 0) {
            for(let i = 0; i < folderDb.length; i++) {
                let folder = folderDb[i];
                if(idMap.hasOwnProperty(folder.parent) ||
                   sortLog.indexOf(folder.parent) !== -1 ||
                   !folder.hasOwnProperty('parent') ||
                   folder.parent === null
                ) {
                    sortLog.push(folder.id);
                    folders.push(folder);
                    folderDb.splice(i, 1);
                    i--;
                }
            }
            if(lastLength === folderDb.length) {
                folders.concat(folderDb);
                break;
            }
            lastLength = folderDb.length;
        }

        return folders;
    }

    /**
     *
     * @param passwords
     * @param mode
     * @param skipShared
     * @param tagMapping
     * @param folderMapping
     * @returns {Promise<{}>}
     */
    async importPasswords(passwords, mode = 0, skipShared = true, tagMapping = {}, folderMapping = {}) {
        this.countProgress('Reading passwords');
        let db    = await API.listPasswords(),
            idMap = {};

        for(let k in db) {
            if(!db.hasOwnProperty(k)) continue;
            idMap[k] = k;
        }

        this.countProgress('Importing passwords');
        for(let i = 0; i < passwords.length; i++) {
            let password = passwords[i];

            if(password.tags) {
                let tags = [];
                for(let j = 0; j < password.tags.length; j++) {
                    let id = password.tags[j];

                    if(tagMapping.hasOwnProperty(id)) {
                        tags.push(tagMapping[id]);
                    }
                }
                password.tags = tags;
            }

            if(folderMapping.hasOwnProperty(password.folder)) {
                password.folder = folderMapping[password.folder];
            } else {
                password.folder = this.defaultFolder;
            }

            try {
                if(mode !== 3 && password.hasOwnProperty('id') && db.hasOwnProperty(password.id)) {
                    let current = db[password.id];
                    if(mode === 1 || (mode === 0 && current.revision === password.revision) || (skipShared && current.share !== null) || !current.editable) {
                        this.countProgress();
                        continue;
                    }

                    idMap[password.id] = password.id;
                    await API.updatePassword(password);
                } else {
                    let info = await API.createPassword(password);
                    idMap[info.id] = info.id;
                }
            } catch(e) {
                console.error(e, password);
                this.errors.push(Localisation.translate('"{error}" in password "{label}".', {label: password.label, error: e.message}));
            }

            this.countProgress();
        }

        return idMap;
    }

    countProgress(status = null) {
        if(status === null) {
            this.processed++;
        } else {
            console.log(`Passwords Import: ${status}`);
        }
        this.progress(this.processed, this.total, status);
    }
}