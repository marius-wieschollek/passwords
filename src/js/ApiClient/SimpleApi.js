import Encryption from './Encryption';

export default class SimpleApi {
    get encryption() {
        return this._encryption;
    }

    get headers() {
        return this._headers;
    }

    get endpoint() {
        return this._endpoint;
    }

    get debug() {
        return this._debug;
    }

    /**
     * SimpleApi Constructor
     *
     * @param endpoint
     * @param username
     * @param password
     * @param token
     * @param debug
     */
    constructor(endpoint, username = null, password = null, token = null, debug = false) {
        this._debug = false;

        this._headers = {};
        this.login(endpoint, username, password, token);

        this._encryption = new Encryption();
        this._paths = {
            'tag.list'         : 'api/1.0/tag/list',
            'tag.find'         : 'api/1.0/tag/find',
            'tag.show'         : 'api/1.0/tag/show',
            'tag.create'       : 'api/1.0/tag/create',
            'tag.update'       : 'api/1.0/tag/update',
            'tag.delete'       : 'api/1.0/tag/delete',
            'tag.restore'      : 'api/1.0/tag/restore',
            'folder.list'      : 'api/1.0/folder/list',
            'folder.find'      : 'api/1.0/folder/find',
            'folder.show'      : 'api/1.0/folder/show',
            'folder.create'    : 'api/1.0/folder/create',
            'folder.update'    : 'api/1.0/folder/update',
            'folder.delete'    : 'api/1.0/folder/delete',
            'folder.restore'   : 'api/1.0/folder/restore',
            'password.list'    : 'api/1.0/password/list',
            'password.find'    : 'api/1.0/password/find',
            'password.show'    : 'api/1.0/password/show',
            'password.create'  : 'api/1.0/password/create',
            'password.update'  : 'api/1.0/password/update',
            'password.delete'  : 'api/1.0/password/delete',
            'password.restore' : 'api/1.0/password/restore',
            'password.generate': 'api/1.0/service/password',
            'service.favicon'  : 'api/1.0/service/icon/{domain}/{size}',
            'service.preview'  : 'api/1.0/service/image/{domain}/{view}/{width}/{height}',
        };
    }

    login(endpoint, username = null, password = null, token = null) {
        this._endpoint = endpoint;
        if (username !== null && password !== null) {
            this._headers['Authorization'] = 'Basic ' + btoa(username + ':' + password);
        }
        if (token !== null) {
            this._headers['X-Passwords-Token'] = token;
        }
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
        data.hash = await this._encryption.getHash(data.password);
        return this._createRequest('password.create', data);
    }

    /**
     * Returns the password with the given id and the given detail level
     *
     * @param id
     * @param detailLevel
     * @returns {Promise}
     */
    showPassword(id, detailLevel = 'model') {
        return this._createRequest('password.show', {id: id, details: detailLevel}, 'POST');
    }

    /**
     * Updates an existing password with the given attributes
     *
     * @param data
     * @returns {Promise}
     */
    async updatePassword(data = {}) {
        data.hash = await this._encryption.getHash(data.password);
        return this._createRequest('password.update', data, 'PATCH');
    }

    /**
     * Deletes the existing password with the given id
     *
     * @param id
     * @returns {Promise}
     */
    deletePassword(id) {
        return this._createRequest('password.delete', {id: id}, 'DELETE');
    }

    /**
     * Restores the existing password with the given id from trash
     *
     * @param id
     * @returns {Promise}
     */
    restorePassword(id) {
        return this._createRequest('password.restore', {id: id}, 'PATCH');
    }

    /**
     * Gets all the passwords, excluding those hidden or in trash
     *
     * @param detailLevel
     * @returns {Promise}
     */
    listPasswords(detailLevel = 'model') {
        return this._createRequest('password.list', {details: detailLevel}, 'POST');
    }

    /**
     * Gets all the passwords matching the criteria, excluding those hidden
     *
     * @param criteria
     * @param detailLevel
     * @returns {Promise}
     */
    findPasswords(criteria = {}, detailLevel = 'model') {
        return this._createRequest('password.find', {details: detailLevel, criteria: criteria}, 'POST');
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
        return this._createRequest('folder.create', data);
    }

    /**
     * Returns the folder with the given id and the given detail level
     *
     * @param id
     * @param detailLevel
     * @returns {Promise}
     */
    showFolder(id = '00000000-0000-0000-0000-000000000000', detailLevel = 'model') {
        return this._createRequest('folder.show', {id: id, details: detailLevel}, 'POST');
    }

    /**
     * Updates an existing folder with the given attributes
     *
     * @param data
     * @returns {Promise}
     */
    async updateFolder(data = {}) {
        return this._createRequest('folder.update', data, 'PATCH');
    }

    /**
     * Deletes the existing folder with the given id
     *
     * @param id
     * @returns {Promise}
     */
    deleteFolder(id) {
        return this._createRequest('folder.delete', {id: id}, 'DELETE');
    }

    /**
     * Restores the existing folder with the given id from trash
     *
     * @param id
     * @returns {Promise}
     */
    restoreFolder(id) {
        return this._createRequest('folder.restore', {id: id}, 'PATCH');
    }

    /**
     * Gets all the folders, excluding those hidden or in trash
     *
     * @param detailLevel
     * @returns {Promise}
     */
    listFolders(detailLevel = 'model') {
        return this._createRequest('folder.list', {details: detailLevel}, 'POST');
    }

    /**
     * Gets all the folders matching the criteria, excluding those hidden
     *
     * @param criteria
     * @param detailLevel
     * @returns {Promise}
     */
    findFolders(criteria = {}, detailLevel = 'model') {
        return this._createRequest('folder.find', {details: detailLevel, criteria: criteria}, 'POST');
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
        return this._createRequest('tag.create', data);
    }

    /**
     * Returns the tag with the given id and the given detail level
     *
     * @param id
     * @param detailLevel
     * @returns {Promise}
     */
    showTag(id, detailLevel = 'model') {
        return this._createRequest('tag.show', {id: id, details: detailLevel}, 'POST');
    }

    /**
     * Updates an existing tag with the given attributes
     *
     * @param data
     * @returns {Promise}
     */
    async updateTag(data = {}) {
        return this._createRequest('tag.update', data, 'PATCH');
    }

    /**
     * Deletes the existing tag with the given id
     *
     * @param id
     * @returns {Promise}
     */
    deleteTag(id) {
        return this._createRequest('tag.delete', {id: id}, 'DELETE');
    }

    /**
     * Restores the existing tag with the given id from trash
     *
     * @param id
     * @returns {Promise}
     */
    restoreTag(id) {
        return this._createRequest('tag.restore', {id: id}, 'PATCH');
    }

    /**
     * Gets all the tag, excluding those hidden or in trash
     *
     * @param detailLevel
     * @returns {Promise}
     */
    listTags(detailLevel = 'model') {
        return this._createRequest('tag.list', {details: detailLevel}, 'POST');
    }

    /**
     * Gets all the tag matching the criteria, excluding those hidden
     *
     * @param criteria
     * @param detailLevel
     * @returns {Promise}
     */
    findTags(criteria = {}, detailLevel = 'model') {
        return this._createRequest('tag.find', {details: detailLevel, criteria: criteria}, 'POST');
    }


    /**
     * Misc Services
     */

    /**
     * Generates a password with the given strength and the given options
     *
     * @param strength
     * @param useNumbers
     * @param useSpecialCharacters
     * @param useSmileys
     * @returns {Promise}
     */
    generatePassword(strength = 1, useNumbers = false, useSpecialCharacters = false, useSmileys = false) {
        if (strength === 1 && useNumbers === false && useSpecialCharacters === false && useSmileys === false) {
            return this._createRequest('password.generate');
        }

        return this._createRequest('password.generate', {
            'strength': strength,
            'numbers' : useNumbers,
            'special' : useSpecialCharacters,
            'smileys' : useSmileys,
        });
    }

    /**
     * Loads a favicon blob over the favicon service
     *
     * @param host
     * @param size
     * @returns {Promise}
     */
    getFavicon(host, size = 24) {
        return this._createRequest(['service.favicon', {domain: host, size: size}], null, 'GET', 'text');
    }

    /**
     * Returns the URL which retrieves the favicon with the given settings
     *
     * @param host
     * @param size
     * @returns {*}
     */
    getFaviconUrl(host, size = 32) {
        return this._endpoint + SimpleApi.processUrl(this._paths['service.favicon'], {domain: host, size: size});
    }

    /**
     * Loads a preview image as blob over the preview service
     *
     * @param host
     * @param view
     * @param width
     * @param height
     * @returns {Promise}
     */
    getPreview(host, view = 'desktop', width = '560', height = '350...') {
        return this._createRequest(
            ['service.preview', {domain: host, view: view, width: width, height: height}],
            null,
            'GET',
            'text'
        );
    }

    /**
     * Returns the URL which retrieves the preview image with the given settings
     *
     * @param host
     * @param view
     * @param width
     * @param height
     * @returns {Promise}
     */
    getPreviewUrl(host, view = 'desktop', width = '560', height = '350...') {
        return this._endpoint + SimpleApi.processUrl(
            this._paths['service.preview'],
            {domain: host, view: view, width: width, height: height}
        );
    }


    /**
     * Internal
     */

    /**
     * Creates an api request
     *
     * @param path
     * @param data
     * @param method
     * @param dataType
     * @returns {Promise}
     * @private
     */
    _createRequest(path, data = null, method = null, dataType = 'json') {

        if (method === null) {
            method = data === null ? 'GET':'POST';
        }

        if (Array.isArray(path)) {
            path = SimpleApi.processUrl(this._paths[path[0]], path[1]);
        } else {
            path = this._paths[path];
        }

        return new Promise((resolve, reject) => {
            $.ajax({
                       type    : method,
                       dataType: dataType,
                       headers : this._headers,
                       url     : this._endpoint + path,
                       data    : data,
                       success : (data) => { resolve(data); },
                       error   : (data) => {
                           try {
                               let response = JSON.parse(data.responseText);
                               data.message = response.message;
                           } catch (e) {
                               data.message = data.status + ': ' + data.statusText;
                           }
                           if (this._debug) console.error(data);
                           reject(data);
                       }
                   });
        });
    }

    /**
     *
     * @param url
     * @param component
     * @returns {*}
     */
    static parseUrl(url, component = null) {
        let link = document.createElement('a');

        if (url.indexOf('://') === -1) url = 'http://' + url;

        link.setAttribute('href', url);

        if (component !== null) return link[component];

        return link;
    }

    /**
     *
     * @param url
     * @param data
     * @returns {*}
     */
    static processUrl(url, data = {}) {
        for (let property in data) {
            if (!data.hasOwnProperty(property)) continue;

            url = url.replace('{' + property + '}', data[property]);
        }

        return url;
    }
}