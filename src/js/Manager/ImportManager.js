import API from '@js/Helper/api';
import Utility from "@js/Classes/Utility";
import SimpleApi from "@/js/ApiClient/SimpleApi";
import * as randomMC from "random-material-color";

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
     * @param options
     * @param progress
     * @returns {Promise<void>}
     */
    async importDatabase(data, type = 'json', options = {}, progress = () => {}) {
        options.mode = Number.parseInt(options.mode);
        this.total = 1;
        this.processed = 0;
        this.progress = progress;
        this.countProgress('Parsing input file');

        switch(type) {
            case 'json':
                data = JSON.parse(data);
                break;
            case 'csv':
                data = await this.processCsv(data, options);
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
            tagMapping = await this.importTags(data.tags, options.mode);
        }

        let folderMapping = {};
        if(data.folders) {
            folderMapping = await this.importFolders(data.folders, options.mode);
        }

        let passwordMapping = {};
        if(data.passwords) {
            if(!data.hasOwnProperty('tags')) tagMapping = await this.getTagMapping();
            if(!data.hasOwnProperty('folders')) folderMapping = await this.getFolderMapping();

            passwordMapping = await this.importPasswords(data.passwords, options.mode, options.skipShared, tagMapping, folderMapping);
        }
    }

    /**
     * Parse a csv file
     *
     * @param csv
     * @param options
     * @returns {{}}
     */
    async processCsv(csv, options) {
        let data    = Utility.parseCsv(csv, options.delimiter),
            mapping = options.mapping,
            db      = [],
            boolYes = Utility.translate('true'),
            boolNo  = Utility.translate('false');

        if(data[0].length < mapping.length) throw "CSV file can not be mapped";
        if(options.firstLine) data.splice(0, options.firstLine);
        data = await this.csvProcessSpecialFields(data, mapping, options.db);

        for(let i = 0; i < data.length; i++) {
            let line   = data[i],
                object = {};

            for(let j = 0; j < line.length; j++) {
                let field = mapping[j];

                if(field.length !== 0) {
                    let value = line[j];

                    if(value === undefined) continue;
                    if(value === boolYes || value === boolNo) {
                        value = value === boolYes;
                    } else if(value === 'yes' || value === 'no') {
                        value = value === 'yes';
                    } else if(value === 'false' || value === 'true') {
                        value = value === 'true';
                    } else if(field === 'edited') {
                        value = new Date(value);
                    } else if(!Number.isNaN(value)) {
                        value = Number.parseInt(value);
                    } else if(field === 'tags' && value.length !== 0) {
                        value = value.split(',');
                    }

                    object[field] = line[j];
                }
            }

            if(options.repair) ImportManager.repairObject(object);
            db.push(object);
        }

        let tmp = {};
        tmp[options.db] = db;

        return tmp;
    }

    /**
     *
     * @param db
     * @param mapping
     * @param dbType
     * @returns {Promise<Array>}
     */
    async csvProcessSpecialFields(db, mapping, dbType) {
        let tagDb = {}, folderDb = {}, folderIdMap = {}, idMap = {}, data = [];

        if(dbType === 'passwords' && mapping.indexOf('id') === -1) {
            let passwords = await API.listPasswords();

            for(let i in passwords) {
                if(!passwords.hasOwnProperty(i)) continue;
                idMap[passwords[i].label] = passwords[i].id;
            }
        }

        if(mapping.indexOf('folderLabel') !== -1 || mapping.indexOf('parentLabel') !== -1) {
            let folders = await API.listFolders();
            folderDb[Utility.translate('Home')] = this.defaultFolder;

            for(let i in folders) {
                if(!folders.hasOwnProperty(i)) continue;
                folderDb[folders[i].label] = folders[i].id;
                folderIdMap[folders[i].id] = folders[i].label;
            }
            if(dbType === 'folders') idMap = folderDb;
        }

        if(mapping.indexOf('tagLabels') !== -1) {
            let tags = await API.listTags();

            for(let i in tags) {
                if(!tags.hasOwnProperty(i)) continue;
                tagDb[tags[i].label] = tags[i].id;
            }
            if(dbType === 'tags') idMap = tagDb;
        }

        if(mapping.indexOf('folderId') !== -1) mapping[mapping.indexOf('folderId')] = 'folder';
        if(mapping.indexOf('parentId') !== -1) mapping[mapping.indexOf('parentId')] = 'parent';
        if(mapping.indexOf('tagIds') !== -1) mapping[mapping.indexOf('tagIds')] = 'tags';
        if(mapping.indexOf('id') === -1) mapping.push('id');

        console.log(folderDb);
        for(let i = 0; i < db.length; i++) {
            let element = db[i], object = element;

            for(let j = 0; j < mapping.length; j++) {
                let field = mapping[j],
                    value = element[j];

                if(field.length === 0) continue;
                if(field === 'id' && value === undefined && mapping.indexOf('label') !== -1) {
                    let label = element[mapping.indexOf('label')];
                    if(idMap.hasOwnProperty(label)) value = idMap[label];
                } else if(field === 'label' && dbType === 'folders') {
                    if(!folderDb.hasOwnProperty(value)) {
                        let folder = await API.createFolder({label: value});
                        folderDb[value] = folder.id;
                    }
                } else if(field === 'folderLabel') {
                    if(value === undefined) { value = Utility.translate('Home'); }
                    if(!folderDb.hasOwnProperty(value)) {
                        let folder = await API.createFolder({label: value});
                        folderDb[value] = folder.id;
                    }
                    if(mapping.indexOf('folder') !== -1) {
                        let folder = element[mapping.indexOf('folder')];

                        if(folderIdMap[folder] === value) {
                            value = folderIdMap[folder];
                        } else {
                            value = folderDb[value];
                        }
                    } else {
                        value = folderDb[value];
                    }
                } else if(field === 'parentLabel') {
                    if(value === undefined) { value = Utility.translate('Home'); }
                    if(!folderDb.hasOwnProperty(value)) {
                        let folder = await API.createFolder({label: value});
                        folderDb[value] = folder.id;
                    }
                    if(mapping.indexOf('parent') !== -1) {
                        let parent = element[mapping.indexOf('parent')];

                        if(folderIdMap[parent] === value) {
                            value = folderIdMap[parent];
                        } else {
                            value = folderDb[value];
                        }
                    } else {
                        value = folderDb[value];
                    }
                } else if(field === 'tagLabels' && value) {
                    let tagLabels = value.split(',');
                    value = [];
                    for(let k = 0; k < tagLabels.length; k++) {
                        let tagLabel = tagLabels[k];

                        if(!tagDb.hasOwnProperty(tagLabel)) {
                            let tag = await API.createTag({label: tagLabel, color: randomMC.getColor()});
                            tagDb[tagLabel] = tag.id;
                        }

                        value.push(tagDb[tagLabel]);
                    }
                }
                object[j] = value;
            }

            data.push(object);
        }

        if(mapping.indexOf('folderLabel') !== -1) mapping[mapping.indexOf('folderLabel')] = 'folder';
        if(mapping.indexOf('parentLabel') !== -1) mapping[mapping.indexOf('parentLabel')] = 'parent';
        if(mapping.indexOf('tagLabels') !== -1) mapping[mapping.indexOf('tagLabels')] = 'tags';

        console.log(data, mapping);

        return data;
    }

    /**
     * Can improve bad input data
     *
     * @param object
     */
    static repairObject(object) {
        if(!object.url || object.url.length === 0) {
            if(object.label && object.label.match(/^([a-zA-Z0-9][a-zA-Z0-9-]{1,61}[a-zA-Z0-9]\.){1,}[a-zA-Z]{2,}$/)) {
                object.url = SimpleApi.parseUrl(object.label, 'href');
                object.label = null;
            }
        }
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
        this.countProgress('Reading folders');
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