export default class SimpleApi {
    get headers() {
        return this._headers;
    }

    get config() {
        return this._config;
    }

    /**
     * SimpleApi Constructor
     */
    constructor() {
        this._config = {};
        this._headers = {};
        this._paths = {
            'tag.list'         : 'api/1.0/tag/list',
            'tag.find'         : 'api/1.0/tag/find',
            'tag.show'         : 'api/1.0/tag/show',
            'tag.create'       : 'api/1.0/tag/create',
            'tag.update'       : 'api/1.0/tag/update',
            'tag.delete'       : 'api/1.0/tag/delete',
            'tag.restore'      : 'api/1.0/tag/restore',
            'share.list'       : 'api/1.0/share/list',
            'share.find'       : 'api/1.0/share/find',
            'share.show'       : 'api/1.0/share/show',
            'share.create'     : 'api/1.0/share/create',
            'share.update'     : 'api/1.0/share/update',
            'share.delete'     : 'api/1.0/share/delete',
            'share.partners'   : 'api/1.0/share/partners',
            'client.list'      : 'api/1.0/client/list',
            'client.show'      : 'api/1.0/client/show',
            'client.create'    : 'api/1.0/client/create',
            'client.update'    : 'api/1.0/client/update',
            'client.delete'    : 'api/1.0/client/delete',
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
            'settings.get'     : 'api/1.0/settings/get',
            'settings.set'     : 'api/1.0/settings/set',
            'settings.list'    : 'api/1.0/settings/list',
            'settings.reset'   : 'api/1.0/settings/reset',
            'token.request'    : 'api/1.0/token/{provider}/request',
            'session.request'  : 'api/1.0/session/request',
            'session.open'     : 'api/1.0/session/open',
            'session.keepalive': 'api/1.0/session/keepalive',
            'session.close'    : 'api/1.0/session/close',
            'keychain.get'     : 'api/1.0/keychain/get',
            'keychain.set'     : 'api/1.0/keychain/set',
            'challenge.get'    : 'api/1.0/account/challenge/get',
            'challenge.set'    : 'api/1.0/account/challenge/set',
            'account.reset'    : 'api/1.0/account/reset',
            'service.coffee'   : 'api/1.0/service/coffee',
            'service.avatar'   : 'api/1.0/service/avatar/{user}/{size}',
            'service.favicon'  : 'api/1.0/service/favicon/{domain}/{size}',
            'service.preview'  : 'api/1.0/service/preview/{domain}/{view}/{width}/{height}',
            'cron.sharing'     : 'cron/sharing'
        };
    }

    /**
     *
     * @param numeric
     * @returns {*}
     */
    static getClientVersion(numeric = false) {
        return numeric ? 30:'0.3.0';
    }

    /**
     * @param config
     */
    initialize(config = {}) {
        this._enabled = false;
        this._config = config;
        if(config.apiUrl.substr(0, 5) !== 'https') throw new Error('HTTPS required for api');

        this._headers = {};
        if(config.headers) this._headers = config.headers;

        if(config.user !== null && config.password !== null) {
            this._headers.Authorization = `Basic ${btoa(`${config.user}:${config.password}`)}`;
        } else {
            throw new Error('Api username or password missing');
        }

        this._enabled = true;
    }


    /**
     * Authentication
     */

    /**
     *
     * @returns {Promise}
     */
    requestToken(provider) {
        return this._sendRequest(['token.request', {provider}]);
    }

    /**
     *
     * @returns {Promise}
     */
    requestSession() {
        return this._sendRequest('session.request');
    }

    /**
     *
     * @returns {Promise}
     */
    openSession(login) {
        return this._sendRequest('session.open', login);
    }

    /**
     *
     * @returns {Promise}
     */
    closeSession() {
        return this._sendRequest('session.close');
    }

    /**
     *
     * @returns {Promise}
     */
    keepaliveSession() {
        return this._sendRequest('session.keepalive');
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
    createPassword(data = {}) {
        return this._sendRequest('password.create', data);
    }

    /**
     * Returns the password with the given id and the given detail level
     *
     * @param id
     * @param details
     * @returns {Promise}
     */
    showPassword(id, details = 'model') {
        return this._sendRequest('password.show', {id, details}, 'POST');
    }

    /**
     * Updates an existing password with the given attributes
     *
     * @param data
     * @returns {Promise}
     */
    updatePassword(data = {}) {
        return this._sendRequest('password.update', data, 'PATCH');
    }

    /**
     * Deletes the existing password with the given id
     *
     * @param id
     * @param revision
     * @returns {Promise}
     */
    deletePassword(id, revision) {
        return this._sendRequest('password.delete', {id, revision}, 'DELETE');
    }

    /**
     * Restores the existing password with the given id from trash
     *
     * @param id
     * @param revision
     * @returns {Promise}
     */
    restorePassword(id, revision = null) {
        return this._sendRequest('password.restore', {id, revision}, 'PATCH');
    }

    /**
     * Gets all the passwords, excluding those hidden or in trash
     *
     * @param details
     * @returns {Promise}
     */
    listPasswords(details = 'model') {
        return this._sendRequest('password.list', {details}, 'POST');
    }

    /**
     * Gets all the passwords matching the criteria, excluding those hidden
     *
     * @param criteria
     * @param details
     * @returns {Promise}
     */
    findPasswords(criteria = {}, details = 'model') {
        return this._sendRequest('password.find', {details, criteria}, 'POST');
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
        return this._sendRequest('folder.create', data);
    }

    /**
     * Returns the folder with the given id and the given detail level
     *
     * @param id
     * @param details
     * @returns {Promise}
     */
    showFolder(id = '00000000-0000-0000-0000-000000000000', details = 'model') {
        return this._sendRequest('folder.show', {id, details}, 'POST');
    }

    /**
     * Updates an existing folder with the given attributes
     *
     * @param data
     * @returns {Promise}
     */
    updateFolder(data = {}) {
        return this._sendRequest('folder.update', data, 'PATCH');
    }

    /**
     * Deletes the existing folder with the given id
     *
     * @param id
     * @param revision
     * @returns {Promise}
     */
    deleteFolder(id, revision) {
        return this._sendRequest('folder.delete', {id, revision}, 'DELETE');
    }

    /**
     * Restores the existing folder with the given id from trash
     *
     * @param id
     * @param revision
     * @returns {Promise}
     */
    restoreFolder(id, revision = null) {
        return this._sendRequest('folder.restore', {id, revision}, 'PATCH');
    }

    /**
     * Gets all the folders, excluding those hidden or in trash
     *
     * @param details
     * @returns {Promise}
     */
    listFolders(details = 'model') {
        return this._sendRequest('folder.list', {details}, 'POST');
    }

    /**
     * Gets all the folders matching the criteria, excluding those hidden
     *
     * @param criteria
     * @param details
     * @returns {Promise}
     */
    findFolders(criteria = {}, details = 'model') {
        return this._sendRequest('folder.find', {details, criteria}, 'POST');
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
        return this._sendRequest('tag.create', data);
    }

    /**
     * Returns the tag with the given id and the given detail level
     *
     * @param id
     * @param details
     * @returns {Promise}
     */
    showTag(id, details = 'model') {
        return this._sendRequest('tag.show', {id, details}, 'POST');
    }

    /**
     * Updates an existing tag with the given attributes
     *
     * @param data
     * @returns {Promise}
     */
    updateTag(data = {}) {
        return this._sendRequest('tag.update', data, 'PATCH');
    }

    /**
     * Deletes the existing tag with the given id
     *
     * @param id
     * @param revision
     * @returns {Promise}
     */
    deleteTag(id, revision) {
        return this._sendRequest('tag.delete', {id, revision}, 'DELETE');
    }

    /**
     * Restores the existing tag with the given id from trash
     *
     * @param id
     * @param revision
     * @returns {Promise}
     */
    restoreTag(id, revision = null) {
        return this._sendRequest('tag.restore', {id, revision}, 'PATCH');
    }

    /**
     * Gets all the tags, excluding those hidden or in trash
     *
     * @param details
     * @returns {Promise}
     */
    listTags(details = 'model') {
        return this._sendRequest('tag.list', {details}, 'POST');
    }

    /**
     * Gets all the tags matching the criteria, excluding those hidden
     *
     * @param criteria
     * @param details
     * @returns {Promise}
     */
    findTags(criteria = {}, details = 'model') {
        return this._sendRequest('tag.find', {details, criteria}, 'POST');
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
        return this._sendRequest('share.create', data);
    }

    /**
     * Returns the share with the given id and the given detail level
     *
     * @param id
     * @param details
     * @returns {Promise}
     */
    showShare(id, details = 'model') {
        return this._sendRequest('share.show', {id, details}, 'POST');
    }

    /**
     * Update a share
     *
     * @param data
     * @returns {Promise}
     */
    updateShare(data = {}) {
        return this._sendRequest('share.update', data, 'PATCH');
    }

    /**
     * Deletes a share
     *
     * @returns {Promise}
     * @param id
     */
    deleteShare(id) {
        return this._sendRequest('share.delete', {id}, 'DELETE');
    }

    /**
     * Gets all the shares, excluding those hidden or in trash
     *
     * @param details
     * @returns {Promise}
     */
    listShares(details = 'model') {
        return this._sendRequest('share.list', {details}, 'POST');
    }

    /**
     * Gets all the shares matching the criteria, excluding those hidden
     *
     * @param criteria
     * @param details
     * @returns {Promise}
     */
    findShares(criteria = {}, details = 'model') {
        return this._sendRequest('share.find', {details, criteria}, 'POST');
    }

    /**
     *
     * @returns {Promise}
     */
    findSharePartners(search = '', limit = 5) {
        if(search.length === 0 && limit === 5) return this._sendRequest('share.partners');
        return this._sendRequest('share.partners', {search, limit}, 'POST');
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
        return this._sendRequest('settings.get', settings, 'POST');
    }

    /**
     *
     * @param settings
     * @returns {Promise}
     */
    setSettings(settings) {
        return this._sendRequest('settings.set', settings, 'POST');
    }

    /**
     *
     * @param settings
     * @returns {Promise}
     */
    resetSettings(settings) {
        return this._sendRequest('settings.reset', settings, 'POST');
    }

    /**
     *
     * @param scopes
     * @returns {Promise}
     */
    listSettings(scopes = null) {
        if(scopes === null) return this._sendRequest('settings.list');
        return this._sendRequest('settings.list', {scopes}, 'POST');
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
            return this._sendRequest('password.generate');
        }

        return this._sendRequest(
            'password.generate',
            {strength, numbers, special}
        );
    }

    /**
     * Loads a favicon blob over the avatar service
     *
     * @param user
     * @param size
     * @returns {Promise}
     */
    getAvatar(user, size = 32) {
        return this._sendRequest(['service.avatar', {user, size}], null, 'GET', 'text');
    }

    /**
     * Returns the URL which retrieves the avatar with the given settings
     *
     * @param user
     * @param size
     * @returns {*}
     */
    getAvatarUrl(user, size = 32) {
        return this._config.apiUrl + SimpleApi.processUrl(this._paths['service.avatar'], {user, size});
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
        return this._sendRequest(['service.favicon', {domain, size}], null, 'GET', 'text');
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
        return this._config.apiUrl + SimpleApi.processUrl(this._paths['service.favicon'], {domain, size});
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
        return this._sendRequest(
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
        return this._config.apiUrl + SimpleApi.processUrl(
            this._paths['service.preview'],
            {domain, view, width, height}
        );
    }


    /**
     * Account Management
     */

    /**
     *
     * @returns {Promise}
     */
    getAccountChallenge() {
        return this._sendRequest('challenge.get');
    }

    /**
     *
     * @returns {Promise}
     */
    setAccountChallenge(salts, secret, oldSecret = null) {
        return this._sendRequest('challenge.set', {salts, secret, oldSecret});
    }

    /**
     *
     * @returns {Promise}
     */
    setKeychain(id, data) {
        return this._sendRequest('keychain.set', {id, data});
    }

    /**
     *
     * @returns {Promise}
     */
    listKeychains() {
        return this._sendRequest('keychain.list');
    }

    /**
     * Resets the user account.
     * First you get a wait time, then you can reset.
     *
     * @returns {Promise}
     */
    resetUserAccount(password) {
        return this._sendRequest('account.reset', {password});
    }


    /**
     * Extra Tasks
     */

    /**
     * Unofficial request to run the sharing update cron job in order to speed up webcron and ajax
     *
     * @returns {Promise}
     */
    runSharingCron() {
        return this._sendRequest('cron.sharing');
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
    async _sendRequest(path, data = null, method = null, dataType = 'json') {
        if(!this._enabled) throw new Error('API not authorized');
        let url = this._getRequestUrl(path),
            options = this._getRequestOptions(method, data, dataType),
            response = await this._executeRequest(url, options),
            contentType  = response.headers.get('content-type');

        this._checkSessionToken(response);

        if(contentType && contentType.indexOf('application/json') !== -1) {
            return await this._processJsonResponse(response);
        } else {
            return await this._processBinaryResponse(response);
        }
    }

    /**
     *
     * @param method
     * @param data
     * @param dataType
     * @returns {{method: *, headers: Headers, credentials: string}}
     * @private
     */
    _getRequestOptions(method, data, dataType) {
        if(method === null || method === 'GET') method = data === null ? 'GET':'POST';

        let headers = new Headers();
        for(let header in this._headers) {
            if(!this._headers.hasOwnProperty(header)) continue;
            headers.append(header, this._headers[header]);
        }
        headers.append('Accept', `application/${dataType}`);

        let options = {method, headers, credentials: 'omit'};
        if(data) {
            headers.append('Content-Type', 'application/json');
            options.body = JSON.stringify(data);
        }

        return options;
    }

    /**
     *
     * @param path
     * @returns {string}
     * @private
     */
    _getRequestUrl(path) {
        if(Array.isArray(path)) {
            path = SimpleApi.processUrl(this._paths[path[0]], path[1]);
        } else {
            path = this._paths[path];
        }

        path = this._config.apiUrl + path;
        return path;
    }

    /**
     *
     * @param url
     * @param options
     * @returns {Promise<Response>}
     * @private
     */
    async _executeRequest(url, options) {
        try {
            return await fetch(new Request(url, options));
        } catch(e) {
            if(e.status === 401 && this._enabled) this._enabled = false;

            this._config.events.emit('api.request.failed', e);
            throw e;
        }
    }

    /**
     *
     * @param response
     * @private
     */
    _checkSessionToken(response) {
        let sessionToken = response.headers.get('X-API-SESSION');

        if(sessionToken && sessionToken !== this._config.sessionToken) {
            this._config.sessionToken = sessionToken;
            this._headers['X-API-SESSION'] = sessionToken;
            this._config.events.emit('api.session.token.changed', {sessionToken});
        }
    }

    /**
     *
     * @param response
     * @returns {Promise<{}>}
     * @private
     */
    async _processJsonResponse(response) {
        let json;
        try {
            json = await response.json();
        } catch(e) {
            e.response = response;
            this._config.events.emit('api.response.decoding.failed', e);
            throw e;
        }

        if(!response.ok) {
            json.response = response;
            this._config.events.emit('api.request.failed', json);
            throw json;
        }

        return json;
    }

    /**
     *
     * @param response
     * @returns {Promise<Blob>}
     * @private
     */
    async _processBinaryResponse(response) {
        let blob;
        try {
            blob = await response.blob();
        } catch(e) {
            e.response = response;
            this._config.events.emit('api.response.decoding.failed', e);
            throw e;
        }

        if(!response.ok) {
            let error = {response, data: blob};
            this._config.events.emit('api.request.failed', error);
            throw error;
        }

        return blob;
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