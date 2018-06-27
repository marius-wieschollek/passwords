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
     * @param debug
     */
    constructor(debug = false) {
        this._debug = debug;

        this._headers = {};
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
            'service.resetAccount':'/api/1.0/service/x-reset-user-account',
            'service.coffee'      : 'api/1.0/service/coffee',
            'service.avatar'      : 'api/1.0/service/avatar/{user}/{size}',
            'service.favicon'     : 'api/1.0/service/favicon/{domain}/{size}',
            'service.preview'     : 'api/1.0/service/preview/{domain}/{view}/{width}/{height}'
        };
    }

    /**
     *
     * @param baseUrl
     * @param username
     * @param password
     */
    login(baseUrl, username = null, password = null) {
        if(baseUrl.substr(0, 5) !== 'https') throw new Error('HTTPS required for api');

        this._endpoint = baseUrl;
        if(username !== null && password !== null) {
            this._headers.Authorization = `Basic ${btoa(`${username}:${password}`)}`;
        }
    }

    /**
     *
     * @param numeric
     * @returns {*}
     */
    static getClientVersion(numeric = false) {
        return numeric ? 20:'0.2.0';
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
     * @param details
     * @returns {Promise}
     */
    showPassword(id, details = 'model') {
        return this._createRequest('password.show', {id, details}, 'POST');
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
        return this._createRequest('password.delete', {id}, 'DELETE');
    }

    /**
     * Restores the existing password with the given id from trash
     *
     * @param id
     * @param revision
     * @returns {Promise}
     */
    restorePassword(id, revision = null) {
        return this._createRequest('password.restore', {id, revision}, 'PATCH');
    }

    /**
     * Gets all the passwords, excluding those hidden or in trash
     *
     * @param details
     * @returns {Promise}
     */
    listPasswords(details = 'model') {
        return this._createRequest('password.list', {details}, 'POST');
    }

    /**
     * Gets all the passwords matching the criteria, excluding those hidden
     *
     * @param criteria
     * @param details
     * @returns {Promise}
     */
    findPasswords(criteria = {}, details = 'model') {
        return this._createRequest('password.find', {details, criteria}, 'POST');
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
     * @param details
     * @returns {Promise}
     */
    showFolder(id = '00000000-0000-0000-0000-000000000000', details = 'model') {
        return this._createRequest('folder.show', {id, details}, 'POST');
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
        return this._createRequest('folder.delete', {id}, 'DELETE');
    }

    /**
     * Restores the existing folder with the given id from trash
     *
     * @param id
     * @param revision
     * @returns {Promise}
     */
    restoreFolder(id, revision = null) {
        return this._createRequest('folder.restore', {id, revision}, 'PATCH');
    }

    /**
     * Gets all the folders, excluding those hidden or in trash
     *
     * @param details
     * @returns {Promise}
     */
    listFolders(details = 'model') {
        return this._createRequest('folder.list', {details}, 'POST');
    }

    /**
     * Gets all the folders matching the criteria, excluding those hidden
     *
     * @param criteria
     * @param details
     * @returns {Promise}
     */
    findFolders(criteria = {}, details = 'model') {
        return this._createRequest('folder.find', {details, criteria}, 'POST');
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
     * @param details
     * @returns {Promise}
     */
    showTag(id, details = 'model') {
        return this._createRequest('tag.show', {id, details}, 'POST');
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
        return this._createRequest('tag.delete', {id}, 'DELETE');
    }

    /**
     * Restores the existing tag with the given id from trash
     *
     * @param id
     * @param revision
     * @returns {Promise}
     */
    restoreTag(id, revision = null) {
        return this._createRequest('tag.restore', {id, revision}, 'PATCH');
    }

    /**
     * Gets all the tags, excluding those hidden or in trash
     *
     * @param details
     * @returns {Promise}
     */
    listTags(details = 'model') {
        return this._createRequest('tag.list', {details}, 'POST');
    }

    /**
     * Gets all the tags matching the criteria, excluding those hidden
     *
     * @param criteria
     * @param details
     * @returns {Promise}
     */
    findTags(criteria = {}, details = 'model') {
        return this._createRequest('tag.find', {details, criteria}, 'POST');
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
     * @param details
     * @returns {Promise}
     */
    showShare(id, details = 'model') {
        return this._createRequest('share.show', {id, details}, 'POST');
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
        return this._createRequest('share.delete', {id}, 'DELETE');
    }

    /**
     * Gets all the shares, excluding those hidden or in trash
     *
     * @param details
     * @returns {Promise}
     */
    listShares(details = 'model') {
        return this._createRequest('share.list', {details}, 'POST');
    }

    /**
     * Gets all the shares matching the criteria, excluding those hidden
     *
     * @param criteria
     * @param details
     * @returns {Promise}
     */
    findShares(criteria = {}, details = 'model') {
        return this._createRequest('share.find', {details, criteria}, 'POST');
    }

    /**
     *
     * @returns {Promise}
     */
    findSharePartners(search = '') {
        if(search.length === 0) return this._createRequest('share.partners');
        return this._createRequest('share.partners', {search}, 'POST');
    }


    /**
     * Settings
     */

    /**
     *
     * @param settings
     * @returns {Promise}
     */
    getSettings(settings) {
        return this._createRequest('settings.get', settings, 'POST');
    }

    /**
     *
     * @param settings
     * @returns {Promise}
     */
    setSettings(settings) {
        return this._createRequest('settings.set', settings, 'POST');
    }

    /**
     *
     * @param settings
     * @returns {Promise}
     */
    resetSettings(settings) {
        return this._createRequest('settings.reset', settings, 'POST');
    }

    /**
     *
     * @param scopes
     * @returns {Promise}
     */
    listSettings(scopes = null) {
        if(scopes === null) return this._createRequest('settings.list');
        return this._createRequest('settings.list', {scopes}, 'POST');
    }


    /**
     * Misc Services
     */

    /**
     * Generates a password with the given strength and the given options
     *
     * @param strength
     * @param numbers
     * @param special
     * @returns {Promise}
     */
    generatePassword(strength, numbers, special) {
        if(strength === undefined && numbers === undefined && special === undefined) {
            return this._createRequest('password.generate');
        }

        return this._createRequest(
            'password.generate',
            {strength, numbers, special}
        );
    }

    /**
     * Resets the user account.
     * First you get a wait time, then you can reset.
     *
     * @returns {Promise}
     */
    resetUserAccount(password) {
        return this._createRequest('service.resetAccount', {password});
    }

    /**
     * Loads a favicon blob over the avatar service
     *
     * @param user
     * @param size
     * @returns {Promise}
     */
    getAvatar(user, size = 32) {
        return this._createRequest(['service.avatar', {user, size}], null, 'GET', 'text');
    }

    /**
     * Returns the URL which retrieves the avatar with the given settings
     *
     * @param user
     * @param size
     * @returns {*}
     */
    getAvatarUrl(user, size = 32) {
        return this._endpoint + SimpleApi.processUrl(this._paths['service.avatar'], {user, size});
    }

    /**
     * Loads a favicon blob over the favicon service
     *
     * @param domain
     * @param size
     * @returns {Promise}
     */
    getFavicon(domain, size = 32) {
        if(domain === null || domain.length === 0) domain = 'default';
        return this._createRequest(['service.favicon', {domain, size}], null, 'GET', 'text');
    }

    /**
     * Returns the URL which retrieves the favicon with the given settings
     *
     * @param domain
     * @param size
     * @returns {*}
     */
    getFaviconUrl(domain, size = 32) {
        if(domain === null || domain.length === 0) domain = 'default';
        return this._endpoint + SimpleApi.processUrl(this._paths['service.favicon'], {domain, size});
    }

    /**
     * Loads a preview image as blob over the preview service
     *
     * @param domain
     * @param view
     * @param width
     * @param height
     * @returns {Promise}
     */
    getPreview(domain, view = 'desktop', width = '640', height = '360...') {
        if(domain === null || domain.length === 0) domain = 'default';
        return this._createRequest(
            ['service.preview', {domain, view, width, height}],
            null,
            'GET',
            'text'
        );
    }

    /**
     * Returns the URL which retrieves the preview image with the given settings
     *
     * @param domain
     * @param view
     * @param width
     * @param height
     * @returns {Promise}
     */
    getPreviewUrl(domain, view = 'desktop', width = '640', height = '360...') {
        if(domain === null || domain.length === 0) domain = 'default';
        return this._endpoint + SimpleApi.processUrl(
            this._paths['service.preview'],
            {domain, view, width, height}
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
        if(method === null || method === 'GET') method = data === null ? 'GET':'POST';

        if(Array.isArray(path)) {
            path = SimpleApi.processUrl(this._paths[path[0]], path[1]);
        } else {
            path = this._paths[path];
        }

        let headers = new Headers();
        for(let header in this._headers) {
            if(!this._headers.hasOwnProperty(header)) continue;
            headers.append(header, this._headers[header]);
        }
        headers.append('Accept', `application/${dataType}, text/plain, */*`);

        let options = {method, headers, credentials: 'omit'};
        if(data) {
            headers.append('Content-Type', 'application/json');
            options.body = JSON.stringify(data);
        }

        return new Promise((resolve, reject) => {
            fetch(new Request(this._endpoint + path, options))
                .then((response) => {
                    let contentType = response.headers.get('content-type');
                    if(contentType && contentType.indexOf('application/json') !== -1) {
                        response.json()
                                .then((d) => {
                                    if(response.ok) {
                                        resolve(d);
                                    } else {
                                        if(this._debug) console.error('Request failed', response, d);
                                        reject(d);
                                    }
                                })
                                .catch((response) => {
                                    if(this._debug) console.error('Decoding response failed', response);
                                    reject(response);
                                });
                    } else {
                        if(response.ok) {
                            resolve(response.blob());
                        } else {
                            if(this._debug) console.error('Request failed', response);
                            reject(response);
                        }
                    }
                })
                .catch((response) => {
                    if(this._debug) console.error('Request failed', response);
                    reject(response);
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
        if(url === undefined) return null;
        let link = document.createElement('a');

        if(url.indexOf('://') === -1) url = `http://${url}`;

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

            url = url.replace(`{${property}}`, data[property]);
        }

        return url;
    }
}