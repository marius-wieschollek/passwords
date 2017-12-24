import SimpleApi from './SimpleApi';

export default class EnhancedApi extends SimpleApi {

    /**
     * EnhancedApi Constructor
     *
     * @param endpoint
     * @param username
     * @param password
     * @param token
     * @param debug
     */
    constructor(endpoint, username = null, password = null, token = null, debug = false) {
        super(endpoint + '/index.php/apps/passwords/', username, password, token, debug);
    }

    /**
     *
     * @param numeric
     * @returns {*}
     */
    static getClientVersion(numeric = false) {
        return numeric ? 100:'0.1.0';
    }


    /**
     * Passwords
     */

    /**
     * Creates a new password with the given attributes
     *
     * @param data
     * @returns {Promise}
     */
    async createPassword(data = {}) {
        try {
            data = EnhancedApi.validatePassword(data);
        } catch (e) {
            return this._createRejectedPromise(e);
        }

        if (!data.label) EnhancedApi._generatePasswordTitle(data);

        return super.createPassword(data);
    }

    /**
     * Update an existing password with the given attributes.
     * If data does not contain an id, a new password will be created.
     *
     * @param data
     * @returns {Promise}
     */
    async updatePassword(data = {}) {
        if (!data.id) return this.createPassword(data);

        try {
            data = EnhancedApi.validatePassword(data);
        } catch (e) {
            return this._createRejectedPromise(e);
        }

        if (!data.label) EnhancedApi._generatePasswordTitle(data);

        return super.updatePassword(data);
    }

    /**
     * Returns the password with the given id and the given detail level
     *
     * @param id
     * @param detailLevel
     * @returns {Promise}
     */
    showPassword(id, detailLevel = 'model') {
        return new Promise((resolve, reject) => {
            super.showPassword(id, detailLevel)
                .then((data) => { resolve(this._processPassword(data)); })
                .catch(reject);
        });
    }

    /**
     * Gets all the passwords, excluding those hidden or in trash
     *
     * @param detailLevel
     * @returns {Promise}
     */
    listPasswords(detailLevel = 'model') {
        return new Promise((resolve, reject) => {
            super.listPasswords(detailLevel)
                .then((data) => { resolve(this._processPasswordList(data)); })
                .catch(reject);
        });
    }

    /**
     * Gets all the passwords matching the criteria
     *
     * @param criteria
     * @param detailLevel
     * @returns {Promise}
     */
    findPasswords(criteria = {}, detailLevel = 'model') {
        return new Promise((resolve, reject) => {
            super.findPasswords(criteria, detailLevel)
                .then((data) => { resolve(this._processPasswordList(data)); })
                .catch(reject);
        });
    }


    /**
     * Folders
     */

    /**
     * Creates a new folder with the given attributes
     *
     * @param data
     * @returns {Promise}
     */
    async createFolder(data = {}) {
        try {
            data = EnhancedApi.validateFolder(data);
        } catch (e) {
            return this._createRejectedPromise(e);
        }

        return super.createFolder(data);
    }

    /**
     * Returns the folder with the given id and the given detail level
     *
     * @param id
     * @param detailLevel
     * @returns {Promise}
     */
    showFolder(id, detailLevel = 'model') {
        return new Promise((resolve, reject) => {
            super.showFolder(id, detailLevel)
                .then((data) => { resolve(this._processFolder(data)); })
                .catch(reject);
        });
    }

    /**
     * Gets all the folders, excluding those hidden or in trash
     *
     * @param detailLevel
     * @returns {Promise}
     */
    listFolders(detailLevel = 'model') {
        return new Promise((resolve, reject) => {
            super.listFolders(detailLevel)
                .then((data) => { resolve(this._processFolderList(data)); })
                .catch(reject);
        });
    }

    /**
     * Gets all the folders matching the criteria
     *
     * @param criteria
     * @param detailLevel
     * @returns {Promise}
     */
    findFolders(criteria = {}, detailLevel = 'model') {
        return new Promise((resolve, reject) => {
            super.findFolders(criteria, detailLevel)
                .then((data) => { resolve(this._processFolderList(data)); })
                .catch(reject);
        });
    }


    /**
     * Tags
     */

    /**
     * Creates a new tag with the given attributes
     *
     * @param data
     * @returns {Promise}
     */
    async createTag(data = {}) {
        try {
            data = EnhancedApi.validateTag(data);
        } catch (e) {
            return this._createRejectedPromise(e);
        }

        return super.createTag(data);
    }

    /**
     * Returns the tag with the given id and the given detail level
     *
     * @param id
     * @param detailLevel
     * @returns {Promise}
     */
    showTag(id, detailLevel = 'model') {
        return new Promise((resolve, reject) => {
            super.showFolder(id, detailLevel)
                .then((data) => { resolve(this._processTag(data)); })
                .catch(reject);
        });
    }

    /**
     * Gets all the tags, excluding those hidden or in trash
     *
     * @param detailLevel
     * @returns {Promise}
     */
    listTags(detailLevel = 'model') {
        return new Promise((resolve, reject) => {
            super.listTags(detailLevel)
                .then((data) => { resolve(this._processTagList(data)); })
                .catch(reject);
        });
    }

    /**
     * Gets all the tags matching the criteria
     *
     * @param criteria
     * @param detailLevel
     * @returns {Promise}
     */
    findTags(criteria = {}, detailLevel = 'model') {
        return new Promise((resolve, reject) => {
            super.findTags(criteria, detailLevel)
                .then((data) => { resolve(this._processTagList(data)); })
                .catch(reject);
        });
    }


    /**
     * Validation
     */

    /**
     *
     * @param password
     * @param strict
     * @returns {Object}
     */
    static validatePassword(password, strict = false) {
        let definitions = EnhancedApi.getPasswordDefinition();
        return EnhancedApi._validateObject(password, definitions, strict);
    }

    /**
     *
     * @param folder
     * @param strict
     * @returns {Object}
     */
    static validateFolder(folder, strict = false) {
        let definitions = EnhancedApi.getFolderDefinition();
        return EnhancedApi._validateObject(folder, definitions, strict);
    }

    /**
     *
     * @param tag
     * @param strict
     * @returns {Object}
     */
    static validateTag(tag, strict = false) {
        let definitions = EnhancedApi.getTagDefinition();
        return EnhancedApi._validateObject(tag, definitions, strict);
    }

    /**
     *
     * @param attributes
     * @param definitions
     * @param strict
     * @returns object
     */
    static _validateObject(attributes, definitions, strict = false) {
        let object = {};

        for (let property in definitions) {
            if (!definitions.hasOwnProperty(property)) continue;
            let definition = definitions[property];

            if (!attributes.hasOwnProperty(property)) {
                if (definition.required) throw "Property " + property + " is required but missing";
                object[property] = definition.hasOwnProperty('default') ? definition.default:null;
                continue;
            }

            let attribute = attributes[property],
                type      = typeof attribute;

            if (definition.required && (!attribute || 0 === attribute.length)) {
                throw "Property " + property + " is required but missing";
            }

            if (definition.type && definition.type !== type && (definition.type !== 'array' || !Array.isArray(attribute))) {
                if (!strict && definition.type === 'boolean') {
                    attribute = Boolean(attribute);
                } else if (!strict && definition.hasOwnProperty('default')) {
                    attribute = definition.default;
                } else if (strict || definition.required) {
                    throw "Property " + property + " has invalid type " + type;
                } else {
                    attribute = null;
                }
            }

            if (definition.length) {
                if (Array.isArray(attribute) && attribute.length > definition.length) {
                    if (strict) throw "Property " + property + " exceeds the maximum length of " + definition.length;
                    attribute = attribute.slice(0, definition.length)
                } else if (type === 'string' && attribute.length > definition.length) {
                    if (strict) throw "Property " + property + " exceeds the maximum length of " + definition.length;
                    attribute = attribute.substr(0, definition.length)
                }
            }

            object[property] = attribute;
        }


        return object;
    }


    /**
     * Internal
     */

    /**
     *
     * @param message
     * @returns {Promise}
     * @private
     */
    _createRejectedPromise(message) {
        return new Promise((resolve, reject) => {
            let error = {status: 'error', message: message};
            if (this._debug) console.error(error);
            reject(error);
        });
    }

    /**
     *
     * @param data
     * @returns {{}}
     * @private
     */
    _processPasswordList(data) {
        let passwords = {};

        for (let i = 0; i < data.length; i++) {
            let password = this._processPassword(data[i]);
            passwords[password.id] = password;
        }

        return passwords;
    }

    /**
     *
     * @param password
     * @returns {{}}
     * @private
     */
    _processPassword(password) {
        password.type = 'password';
        if (password.url) {
            let host = SimpleApi.parseUrl(password.url, 'host');
            password.icon = this.getFaviconUrl(host);
            password.image = this.getPreviewUrl(host);
        } else {
            password.icon = this.getFaviconUrl(null);
            password.image = this.getPreviewUrl(null);
        }
        password.created = new Date(password.created * 1e3);
        password.updated = new Date(password.updated * 1e3);

        return password;
    }

    /**
     *
     * @param data
     * @returns {{}}
     * @private
     */
    _processFolderList(data) {
        let folders = {};

        for (let i = 0; i < data.length; i++) {
            let folder = this._processFolder(data[i]);
            folders[folder.id] = folder;
        }

        return folders;
    }

    /**
     *
     * @param folder
     * @returns {{}}
     * @private
     */
    _processFolder(folder) {
        folder.type = 'folder';
        folder.icon = 'http://localhost/core/img/filetypes/folder.svg';
        if (folder.folders) {
            folder.folders = this._processFolderList(folder.folders);
        }
        if (folder.passwords) {
            folder.passwords = this._processPasswordList(folder.passwords);
        }
        if (typeof folder.parent !== 'string') {
            folder.parent = this._processFolder(folder.parent);
        }
        folder.created = new Date(folder.created * 1e3);
        folder.updated = new Date(folder.updated * 1e3);

        return folder;
    }

    /**
     *
     * @param data
     * @returns {{}}
     * @private
     */
    _processTagList(data) {
        let tags = {};

        for (let i = 0; i < data.length; i++) {
            let tag = this._processTag(data[i]);
            tags[tag.id] = tag;
        }

        return tags;
    }

    /**
     *
     * @param tag
     * @returns {{}}
     * @private
     */
    _processTag(tag) {
        tag.type = 'tag';
        if (tag.passwords) {
            tag.passwords = this._processPasswordList(tag.passwords);
        }
        tag.created = new Date(tag.created * 1e3);
        tag.updated = new Date(tag.updated * 1e3);

        return tag;
    }

    /**
     * Generates an automatic title from the given data
     *
     * @param data
     * @returns string
     * @private
     */
    static _generatePasswordTitle(data) {
        data.label = String(data.username);
        if (data.url) {
            data.label += '@' + SimpleApi.parseUrl(data.url, 'host').replace('www.', '');
        }
    }


    /**
     * Object Definitions
     */

    /**
     *
     * @returns object
     */
    static getPasswordDefinition() {
        return {
            id       : {
                type  : 'string',
                length: 36
            },
            username : {
                type    : 'string',
                length  : 48,
                required: true
            },
            password : {
                type    : 'string',
                length  : 48,
                required: true
            },
            label    : {
                type   : 'string',
                length : 48,
                default: null
            },
            url      : {
                type   : 'string',
                length : 2048,
                default: null
            },
            notes    : {
                type   : 'string',
                length : 4096,
                default: null
            },
            folder   : {
                type   : 'string',
                length : 36,
                default: '00000000-0000-0000-0000-000000000000'
            },
            cseType  : {
                type   : 'string',
                length : 10,
                default: 'none'
            },
            sseType  : {
                type   : 'string',
                length : 10,
                default: null
            },
            hidden   : {
                type   : 'boolean',
                default: false
            },
            trashed  : {
                type   : 'boolean',
                default: false
            },
            favourite: {
                type   : 'boolean',
                default: false
            },
            tags     : {
                type   : 'array',
                default: []
            }
        }
    }

    /**
     *
     * @returns object
     */
    static getFolderDefinition() {
        return {
            id       : {
                type  : 'string',
                length: 36
            },
            label    : {
                type    : 'string',
                length  : 48,
                required: true
            },
            parent   : {
                type   : 'string',
                length : 36,
                default: '00000000-0000-0000-0000-000000000000'
            },
            cseType  : {
                type   : 'string',
                length : 10,
                default: 'none'
            },
            sseType  : {
                type   : 'string',
                length : 10,
                default: null
            },
            hidden   : {
                type   : 'boolean',
                default: false
            },
            trashed  : {
                type   : 'boolean',
                default: false
            },
            favourite: {
                type   : 'boolean',
                default: false
            }
        }
    }

    /**
     *
     * @returns object
     */
    static getTagDefinition() {
        return {
            id       : {
                type  : 'string',
                length: 36
            },
            label    : {
                type    : 'string',
                length  : 48,
                required: true
            },
            color    : {
                type    : 'string',
                length  : 48,
                required: true
            },
            cseType  : {
                type   : 'string',
                length : 10,
                default: 'none'
            },
            sseType  : {
                type   : 'string',
                length : 10,
                default: null
            },
            hidden   : {
                type   : 'boolean',
                default: false
            },
            trashed  : {
                type   : 'boolean',
                default: false
            },
            favourite: {
                type   : 'boolean',
                default: false
            },
            passwords: {
                type   : 'array',
                default: []
            }
        }
    }
}