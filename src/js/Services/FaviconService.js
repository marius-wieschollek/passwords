import SettingsService from "@js/Services/SettingsService";
import ClientService from "@js/Services/ClientService";
import LoggingService from "@js/Services/LoggingService";

export default new class FaviconService {

    /**
     *
     */
    constructor() {
        this._service = null;
        this._cache = [];
        this._db = null;

        this._initIconCache();
    }

    /**
     * Return icon if in cache, otherwise return default icon
     *
     * @param {String} domain
     * @param {Number} size
     * @return {String}
     */
    get(domain, size = 32) {
        if(this._cache.hasOwnProperty(`${domain}_${size}`)) {
            return this._cache[`${domain}_${size}`];
        }

        return this._getService().get(domain, size);
    }

    /**
     *
     * @param {String} domain
     * @param {Number} size
     * @return {Promise<String>}
     */
    async fetch(domain, size = 32) {
        if(this._cache.hasOwnProperty(`${domain}_${size}`)) {
            return this._cache[`${domain}_${size}`];
        }

        let data = await this._getService().fetch(domain, size);
        this._writeIconToCache(`${domain}_${size}`, data)
            .catch(LoggingService.catch);

        if(data instanceof Blob) {
            data = URL.createObjectURL(data);
        }

        this._cache[`${domain}_${size}`] = data;
        return data;
    }

    /**
     *
     * @return {FaviconService}
     * @private
     */
    _getService() {
        if(this._service === null) {
            let fallbackIcon = SettingsService.get('server.theme.app.icon');

            /** @type FaviconService **/
            this._service = ClientService.getClient().getInstance('service.favicon', fallbackIcon);
        }

        return this._service;
    }

    /**
     * Init the cache and load cached icons
     *
     * @return {Promise<void>}
     * @private
     */
    async _initIconCache() {
        if(!window.indexedDB) {
            return;
        }

        try {
            await this._initDatabase();
        } catch(e) {
            return;
        }

        this._loadIconsFromCache();
    }

    /**
     * Load the icon cache database
     *
     * @return {*}
     * @private
     */
    _initDatabase() {
        return new Promise((resolve, reject) => {
            let request = window.indexedDB.open('pw_cache', 1);
            request.onerror = (event) => {
                LoggingService.error(`Could not open image cache: ${request.error}`, request);
                reject(event);
            };

            request.onupgradeneeded = (event) => {
                let db = event.target.result;

                db.createObjectStore('favicons', {keyPath: 'key'});
            };

            request.onsuccess = (event) => {
                this._db = event.target.result;
                resolve();
            };
        });
    }

    /**
     * Load icons from cache
     * Discard icons older than one week
     *
     * @private
     */
    _loadIconsFromCache() {
        let transaction = this._db.transaction(['favicons'], 'readonly'),
            request     = transaction.objectStore('favicons').getAll();

        request.onsuccess = (e) => {
            let maxAge   = (Date.now() / 1000) - (7 * 24 * 60 * 60),
                entries  = e.target.result,
                toRemove = [];

            for(let entry of entries) {
                let value = JSON.parse(entry.value);
                if(value.time > maxAge && this._cache.length < 1024) {
                    let blob = new Blob([Uint8Array.from(value.data.split(','))]);
                    this._cache[entry.key] = URL.createObjectURL(blob);
                } else {
                    toRemove.push(entry.key);
                }
            }

            this._removeIconsFromCache(toRemove);
        };
    }

    /**
     * Remove icons from the cache
     *
     * @param keys
     * @private
     */
    _removeIconsFromCache(keys) {
        let transaction = this._db.transaction(['favicons'], 'readwrite');

        for(let key of keys) {
            transaction.objectStore('favicons').delete(key);
        }
    }

    /**
     * Store a single icon in the cache as byte string
     *
     * @param key
     * @param value
     * @return {Promise<void>}
     * @private
     */
    async _writeIconToCache(key, value) {
        if(!(value instanceof Blob) || this._cache.length > 1024) {
            return;
        }

        let data        = await value.bytes(),
            object      = JSON.stringify({time: Date.now() / 1000, data: data.toString()}),
            transaction = this._db.transaction(['favicons'], 'readwrite');

        transaction.objectStore('favicons').put({key, value: object});
    }
};