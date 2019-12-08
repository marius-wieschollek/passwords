import API from '@js/Helper/api';
import Localisation from '@js/Classes/Localisation';
import SettingsService from '@js/Services/SettingsService';
import ImportCsvConversionHelper from '@js/Helper/Import/CsvConversionHelper';
import EnpassConversionHelper from '@js/Helper/Import/EnpassConversionHelper';
import ImportJsonConversionHelper from '@js/Helper/Import/JsonConversionHelper';
import PassmanConversionHelper from '@js/Helper/Import/PassmanConversionHelper';
import BitwardenConversionHelper from '@js/Helper/Import/BitwardenConversionHelper';

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
        options.cseType = SettingsService.get('user.encryption.cse') === 1 ? 'CSEv1r1':'none';
        this.errors = [];
        this.total = 1;
        this.processed = 0;
        this.progress = progress;
        this._countProgress('Parsing input file');

        let result = await ImportManager._convertInputData(type, data, options);
        this.errors = result.errors;
        data = result.data;

        this.total = 0;
        for(let k in data) {
            if(!data.hasOwnProperty(k) || !Array.isArray(data[k])) continue;
            this.total += data[k].length;
        }

        let tagMapping = {}, folderMapping = {};
        tagMapping = await this._runTagImport(data, tagMapping, options);
        folderMapping = await this._runFolderImport(data, folderMapping, options);
        await this._runPasswordImport(data, tagMapping, folderMapping, options);

        return this.errors;
    }

    /**
     *
     * @param type
     * @param data
     * @param options
     * @returns {Promise<*>}
     * @private
     */
    static async _convertInputData(type, data, options) {
        switch(type) {
            case 'json':
                return await ImportJsonConversionHelper.processBackupJson(data, options);
            case 'pmanJson':
                return await PassmanConversionHelper.processJson(data);
            case 'pmanCsv':
                return await ImportCsvConversionHelper.processPassmanCsv(data);
            case 'csv':
                return await ImportCsvConversionHelper.processGenericCsv(data, options);
            case 'enpass':
                return await EnpassConversionHelper.processJson(data, options);
            case 'bitwarden':
                return await BitwardenConversionHelper.processJson(data, options);
            default:
                throw new Error(`Invalid import type: ${type}`);
        }
    }

    /**
     *
     * @param data
     * @param tagMapping
     * @param options
     * @returns {Promise<*>}
     * @private
     */
    async _runTagImport(data, tagMapping, options) {
        try {
            if(data.tags) tagMapping = await this._importTags(data.tags, options.mode, options.cseType);
        } catch(e) {
            console.error(e);
            throw new Error('Unable to create tags');
        }
        return tagMapping;
    }

    /**
     *
     * @param data
     * @param folderMapping
     * @param options
     * @returns {Promise<*>}
     * @private
     */
    async _runFolderImport(data, folderMapping, options) {
        try {
            if(data.folders) folderMapping = await this._importFolders(data.folders, options.mode, options.cseType);
        } catch(e) {
            console.error(e);
            throw new Error('Unable to create folders');
        }
        return folderMapping;
    }

    /**
     *
     * @param data
     * @param tagMapping
     * @param folderMapping
     * @param options
     * @returns {Promise<void>}
     * @private
     */
    async _runPasswordImport(data, tagMapping, folderMapping, options) {
        if(data.passwords) {
            if(!data.hasOwnProperty('tags')) tagMapping = await this._getTagMapping();
            if(!data.hasOwnProperty('folders')) folderMapping = await this._getFolderMapping();

            try {
                await this._importPasswords(data.passwords, options.mode, options.skipShared, options.cseType, tagMapping, folderMapping);
            } catch(e) {
                console.error(e);
                throw new Error('Unable to create passwords');
            }
        }
    }

    /**
     *
     * @returns {Promise<{}>}
     * @private
     */
    async _getTagMapping() {
        this._countProgress('Analyzing tags');

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
     * @private
     */
    async _getFolderMapping() {
        this._countProgress('Analyzing folders');

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
     * @param cseType
     * @returns {Promise<{}>}
     * @private
     */
    async _importTags(tags, mode = 0, cseType = 'none') {
        this._countProgress('Reading tags');
        let db    = await API.listTags(),
            queue = [],
            idMap = {};

        for(let k in db) {
            if(!db.hasOwnProperty(k)) continue;
            idMap[k] = k;
        }

        this._countProgress('Importing tags');
        for(let i = 0; i < tags.length; i++) {
            queue.push(this._importTag(tags[i], mode, cseType, db, idMap));

            if(queue.length > 15) {
                await Promise.all(queue);
                queue = [];
            }
        }

        if(queue.length !== 0) await Promise.all(queue);

        return idMap;
    }

    /**
     *
     * @param tag
     * @param mode
     * @param cseType
     * @param db
     * @param idMap
     * @returns {Promise<void>}
     * @private
     */
    async _importTag(tag, mode, cseType, db, idMap) {
        try {
            if(mode !== 4 && tag.hasOwnProperty('id') && db.hasOwnProperty(tag.id)) {
                let object = this._processGenericObject(tag, db[tag.id], mode, cseType, idMap);
                if(object !== false) await API.updateTag(object);
            } else {
                let info = await API.createTag(tag);
                idMap[tag.id] = info.id;
            }
        } catch(e) {
            console.error(e, tag);
            this.errors.push(Localisation.translate('"{error}" in tag "{label}".', {label: tag.label, error: e.message}));
        }

        this._countProgress();
    }

    /**
     *
     * @param folderDb
     * @param mode
     * @param cseType
     * @returns {Promise<{}>}
     * @private
     */
    async _importFolders(folderDb, mode = 0, cseType = 'none') {
        this._countProgress('Reading folders');
        let db    = await API.listFolders(),
            queue = [],
            idMap = {};


        idMap[this.defaultFolder] = this.defaultFolder;
        for(let k in db) {
            if(!db.hasOwnProperty(k)) continue;
            idMap[k] = k;
        }
        let folders = ImportManager._sortFoldersForImport(folderDb, idMap);

        this._countProgress('Importing folders');
        for(let i = 0; i < folders.length; i++) {
            let folder = folders[i];

            if(folder.id === this.defaultFolder) {
                this._countProgress();
                continue;
            }

            if(!idMap.hasOwnProperty(folder.parent) || queue.length > 15) {
                await Promise.all(queue);
                queue = [];
            }

            queue.push(this._importFolder(folder, mode, cseType, db, idMap));
        }

        if(queue.length !== 0) await Promise.all(queue);

        return idMap;
    }

    /**
     *
     * @param folder
     * @param mode
     * @param cseType
     * @param db
     * @param idMap
     * @returns {Promise<void>}
     * @private
     */
    async _importFolder(folder, mode, cseType, db, idMap) {

        if(!idMap.hasOwnProperty(folder.parent) || folder.parent === folder.id) {
            folder.parent = this.defaultFolder;
        } else {
            folder.parent = idMap[folder.parent];
        }

        try {
            if(mode !== 4 && folder.hasOwnProperty('id') && db.hasOwnProperty(folder.id)) {
                let object = this._processGenericObject(folder, db[folder.id], mode, cseType, idMap);
                if(object !== false) await API.updateFolder(object);
            } else {
                let info = await API.createFolder(folder);
                idMap[folder.id] = info.id;
            }
        } catch(e) {
            console.error(e, folder);
            this.errors.push(Localisation.translate('"{error}" in folder "{label}".', {label: folder.label, error: e.message}));
        }

        this._countProgress();
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
     * @param cseType
     * @param tagMapping
     * @param folderMapping
     * @returns {Promise<{}>}
     * @private
     */
    async _importPasswords(passwords, mode = 0, skipShared = true, cseType = 'none', tagMapping = {}, folderMapping = {}) {
        this._countProgress('Reading passwords');
        let db    = await API.listPasswords(),
            queue = [],
            idMap = {};

        for(let k in db) {
            if(!db.hasOwnProperty(k)) continue;
            idMap[k] = k;
        }

        this._countProgress('Importing passwords');
        for(let i = 0; i < passwords.length; i++) {
            queue.push(this._importPassword(mode, passwords[i], db, skipShared, cseType, idMap, folderMapping, tagMapping));

            if(queue.length > 15) {
                await Promise.all(queue);
                queue = [];
            }
        }

        if(queue.length !== 0) await Promise.all(queue);

        return idMap;
    }

    /**
     *
     * @param mode
     * @param password
     * @param db
     * @param skipShared
     * @param cseType
     * @param idMap
     * @param folderMapping
     * @param tagMapping
     * @returns {Promise<void>}
     * @private
     */
    async _importPassword(mode, password, db, skipShared, cseType, idMap, folderMapping, tagMapping) {

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
            if(mode !== 4 && password.hasOwnProperty('id') && db.hasOwnProperty(password.id)) {
                let current = db[password.id];
                if(mode === 1 || (mode === 0 && current.revision === password.revision) || (skipShared && current.share !== null) || !current.editable) {
                    this._countProgress();
                    return;
                }

                if(mode === 3) {
                    password = Object.assign(db[password.id], password);
                } else if(!current.shared && current.share === null) {
                    console.log(current.shared, current.share);
                    password.cseType = cseType;
                } else {
                    password.cseType = 'none';
                }

                idMap[password.id] = password.id;
                await API.updatePassword(password);
            } else {
                password.cseType = cseType;
                let info = await API.createPassword(password);
                idMap[info.id] = info.id;
            }
        } catch(e) {
            let message = e.hasOwnProperty('message') ? e.message:e.statusText;
            console.error(e, password);
            this.errors.push(Localisation.translate('"{error}" in password "{label}".', {label: password.label, error: message}));
        }

        this._countProgress();
    }

    /**
     *
     * @param mode
     * @param object
     * @param baseObject
     * @param cseType
     * @param idMap
     * @returns {boolean|*}
     * @private
     */
    _processGenericObject(object, baseObject, mode, cseType, idMap) {
        if(mode === 1 || (mode === 0 && baseObject.revision === object.revision)) {
            this._countProgress();
            return false;
        }

        if(mode === 3) {
            object = Object.assign(baseObject, object);
        } else {
            object.cseType = cseType;
        }

        idMap[object.id] = object.id;
        return object;
    }

    /**
     *
     * @param status
     * @private
     */
    _countProgress(status = null) {
        if(status === null) {
            this.processed++;
        } else {
            console.info(`Passwords Import: ${status}`);
        }
        this.progress(this.processed, this.total, status);
    }
}