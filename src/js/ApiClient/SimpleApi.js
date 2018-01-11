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
     * @param debug
     */
    constructor(endpoint, username = null, password = null, debug = false) {
        this._debug = debug;

        this._headers = {};
        this.login(endpoint, username, password);

        this._encryption = new Encryption();
        this._paths = {
            'tag.list'            : 'api/1.0/tag/list',
            'tag.find'            : 'api/1.0/tag/find',
            'tag.show'            : 'api/1.0/tag/show',
            'tag.create'          : 'api/1.0/tag/create',
            'tag.update'          : 'api/1.0/tag/update',
            'tag.delete'          : 'api/1.0/tag/delete',
            'tag.restore'         : 'api/1.0/tag/restore',
            'share.list'          : 'api/1.0/share/list',
            'share.find'          : 'api/1.0/share/find',
            'share.show'          : 'api/1.0/share/show',
            'share.info'          : 'api/1.0/share/info',
            'share.create'        : 'api/1.0/share/create',
            'share.update'        : 'api/1.0/share/update',
            'share.delete'        : 'api/1.0/share/delete',
            'share.partners'      : 'api/1.0/share/partners',
            'client.list'         : 'api/1.0/client/list',
            'client.show'         : 'api/1.0/client/show',
            'client.create'       : 'api/1.0/client/create',
            'client.update'       : 'api/1.0/client/update',
            'client.delete'       : 'api/1.0/client/delete',
            'folder.list'         : 'api/1.0/folder/list',
            'folder.find'         : 'api/1.0/folder/find',
            'folder.show'         : 'api/1.0/folder/show',
            'folder.create'       : 'api/1.0/folder/create',
            'folder.update'       : 'api/1.0/folder/update',
            'folder.delete'       : 'api/1.0/folder/delete',
            'folder.restore'      : 'api/1.0/folder/restore',
            'password.list'       : 'api/1.0/password/list',
            'password.find'       : 'api/1.0/password/find',
            'password.show'       : 'api/1.0/password/show',
            'password.create'     : 'api/1.0/password/create',
            'password.update'     : 'api/1.0/password/update',
            'password.delete'     : 'api/1.0/password/delete',
            'password.restore'    : 'api/1.0/password/restore',
            'password.generate'   : 'api/1.0/service/password',
            'settings.get'        : 'api/1.0/settings/get',
            'settings.set'        : 'api/1.0/settings/set',
            'settings.list'       : 'api/1.0/settings/list',
            'settings.reset'      : 'api/1.0/settings/reset',
            'authorisation.info'  : 'api/1.0/authorisation/info',
            'authorisation.login' : 'api/1.0/authorisation/login',
            'authorisation.logout': 'api/1.0/authorisation/logout',
            'service.coffee'      : 'api/1.0/service/coffee',
            'service.avatar'      : 'api/1.0/service/avatar/{user}/{size}',
            'service.favicon'     : 'api/1.0/service/favicon/{domain}/{size}',
            'service.preview'     : 'api/1.0/service/image/{domain}/{view}/{width}/{height}',
        };
    }

    login(endpoint, username = null, password = null) {
        this._endpoint = endpoint;
        if(username !== null && password !== null) {
            this._headers.Authorization = 'Basic ' + btoa(username + ':' + password);
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
     * @param revision
     * @returns {Promise}
     */
    restorePassword(id, revision = null) {
        return this._createRequest('password.restore', {id: id, revision: revision}, 'PATCH');
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
    createFolder(data = {}) {
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
    updateFolder(data = {}) {
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
     * @param revision
     * @returns {Promise}
     */
    restoreFolder(id, revision = null) {
        return this._createRequest('folder.restore', {id: id, revision: revision}, 'PATCH');
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
    createTag(data = {}) {
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
    updateTag(data = {}) {
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
     * @param revision
     * @returns {Promise}
     */
    restoreTag(id, revision = null) {
        return this._createRequest('tag.restore', {id: id, revision: revision}, 'PATCH');
    }

    /**
     * Gets all the tags, excluding those hidden or in trash
     *
     * @param detailLevel
     * @returns {Promise}
     */
    listTags(detailLevel = 'model') {
        return this._createRequest('tag.list', {details: detailLevel}, 'POST');
    }

    /**
     * Gets all the tags matching the criteria, excluding those hidden
     *
     * @param criteria
     * @param detailLevel
     * @returns {Promise}
     */
    findTags(criteria = {}, detailLevel = 'model') {
        return this._createRequest('tag.find', {details: detailLevel, criteria: criteria}, 'POST');
    }


    /**
     * Sharing
     */

    /**
     * Creates a new share with the given attributes
     *
     * @param data
     * @returns {Promise}
     */
    createShare(data = {}) {
        return this._createRequest('share.create', data);
    }

    /**
     * Returns the share with the given id and the given detail level
     *
     * @param id
     * @param detailLevel
     * @returns {Promise}
     */
    showShare(id, detailLevel = 'model') {
        return this._createRequest('share.show', {id: id, details: detailLevel}, 'POST');
    }

    /**
     * Update a share
     *
     * @param data
     * @returns {Promise}
     */
    updateShare(data = {}) {
        return this._createRequest('share.update', data, 'PATCH');
    }

    /**
     * Deletes a share
     *
     * @returns {Promise}
     * @param id
     */
    deleteShare(id) {
        return this._createRequest('share.delete', {id: id}, 'DELETE');
    }

    /**
     * Gets all the shares, excluding those hidden or in trash
     *
     * @param detailLevel
     * @returns {Promise}
     */
    listShares(detailLevel = 'model') {
        return this._createRequest('share.list', {details: detailLevel}, 'POST');
    }

    /**
     * Gets all the shares matching the criteria, excluding those hidden
     *
     * @param criteria
     * @param detailLevel
     * @returns {Promise}
     */
    findShares(criteria = {}, detailLevel = 'model') {
        return this._createRequest('share.find', {details: detailLevel, criteria: criteria}, 'POST');
    }

    /**
     *
     * @returns {Promise}
     */
    getSharingInfo() {
        return this._createRequest('share.info');
    }

    /**
     *
     * @returns {Promise}
     */
    findSharePartners(search = '') {
        if(search.length === 0) {
            return this._createRequest('share.partners');
        }
        return this._createRequest('share.partners', {search: search}, 'POST');
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
        if(strength === 1 && useNumbers === false && useSpecialCharacters === false && useSmileys === false) {
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
     * Loads a favicon blob over the avatar service
     *
     * @param user
     * @param size
     * @returns {Promise}
     */
    getAvatar(user, size = 32) {
        return this._createRequest(['service.avatar', {user: user, size: size}], null, 'GET', 'text');
    }

    /**
     * Returns the URL which retrieves the avatar with the given settings
     *
     * @param user
     * @param size
     * @returns {*}
     */
    getAvatarUrl(user, size = 32) {
        return this._endpoint + SimpleApi.processUrl(this._paths['service.avatar'], {user: user, size: size});
    }

    /**
     * Loads a favicon blob over the favicon service
     *
     * @param host
     * @param size
     * @returns {Promise}
     */
    getFavicon(host, size = 32) {
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
    getPreview(host, view = 'desktop', width = '550', height = '350...') {
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
/*    _createRequest(path, data = null, method = null, dataType = 'json') {

        if(method === null) {
            method = data === null ? 'GET':'POST';
        }

        if(Array.isArray(path)) {
            path = SimpleApi.processUrl(this._paths[path[0]], path[1]);
        } else {
            path = this._paths[path];
        }
        this._headers['Cookie'] = '';

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
                           } catch(e) {
                               data.message = data.status + ': ' + data.statusText;
                           }
                           if(this._debug) console.error(data);
                           reject(data);
                       }
                   });
        });
    }*/
    _createRequest(path, data = null, method = null, dataType = 'json') {
        if(method === null) {
            method = data === null ? 'GET':'POST';
        }

        if(Array.isArray(path)) {
            path = SimpleApi.processUrl(this._paths[path[0]], path[1]);
        } else {
            path = this._paths[path];
        }

        let headers = new Headers();
        for (let header in this._headers) {
            if (!this._headers.hasOwnProperty(header)) continue;
            headers.append(header, this._headers[header]);
        }
        headers.append('Accept', 'application/' + dataType + ', text/plain, */*');
        headers.append('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');

        if (data && method === 'GET') method = 'POST';
        let options = {
            method : method,
            headers: headers
        };
        if (data) options.body = JSON.stringify(data);
        let request = new Request(
            this._endpoint + path,
            options
        );

        return new Promise((resolve, reject) => {
            fetch(request)
                .then((response) => {
                    if (!response.ok) {
                        if (this._debug) console.error('Request failed', request, response);
                        reject(response)
                    }
                    response.json()
                        .then((d) => {resolve(d);})
                        .catch((response) => {
                            if (this._debug) console.error('Encoding response failed', request, response);
                            reject(response)
                        })
                })
                .catch((response) => {
                    if (this._debug) console.error('Request failed', request, response);
                    reject(response)
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

        if(url.indexOf('://') === -1) url = 'http://' + url;

        link.setAttribute('href', url);

        if(component !== null) return link[component];

        return link;
    }

    /**
     *
     * @param url
     * @param data
     * @returns {*}
     */
    static processUrl(url, data = {}) {
        for(let property in data) {
            if(!data.hasOwnProperty(property)) continue;

            url = url.replace('{' + property + '}', data[property]);
        }

        return url;
    }
}