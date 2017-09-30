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
        this._endpoint = endpoint;
        this._debug = false;

        this._headers = {};
        if (username !== null && password !== null) {
            this._headers['Authorization'] = 'Basic ' + username + ':' + password;
        }
        if (token !== null) {
            this._headers['X-Passwords-Token'] = token;
        }

        this._encryption = new Encryption();
        this._paths = {
            'password.list'    : 'api/1.0/password/list',
            'password.create'  : 'api/1.0/password/create',
            'password.update'  : 'api/1.0/password/update/{id}',
            'password.delete'  : 'api/1.0/password/delete/{id}',
            'password.generate': 'api/1.0/service/password',
            'service.favicon'  : 'api/1.0/service/icon/{domain}/{size}',
            'service.preview'  : 'api/1.0/service/image/{domain}/{view}/{width}/{height}',
        };
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
     * Creates a new password with the given attributes
     *
     * @param data
     * @returns {Promise}
     */
    createPassword(data = {}) {
        data.hash = this._encryption.getHash(data.password);
        return this._createRequest('password.create', data);
    }

    /**
     * Updates an existing password with the given attributes
     *
     * @param data
     * @returns {Promise}
     */
    updatePassword(data = {}) {
        data.hash = this._encryption.getHash(data.password);
        return this._createRequest(['password.update', data], data, 'PATCH');
    }

    /**
     * Deletes the existing password with the given id
     *
     * @param id
     * @returns {Promise}
     */
    deletePassword(id) {
        return this._createRequest(['password.delete', {id: id}], null, 'DELETE');
    }

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
     * Gets all the passwords, including those in the trash
     *
     * @param detailLevel
     * @returns {Promise}
     */
    listPasswords(detailLevel = 'default') {

        if (detailLevel !== 'default') {
            return this._createRequest(
                'password.list',
                {level: detailLevel},
                'POST'
            );
        }

        return this._createRequest('password.list');
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