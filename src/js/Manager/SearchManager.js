import router from '@js/Helper/router';
import SettingsManager from '@js/Manager/SettingsManager';

class SearchManager {

    get status() {
        return this._status;
    }

    constructor() {
        this._db = {};
        this._status = {active: false, available: false, query: '', total: 0, passwords: 0, folders: 0, tags: 0, time: 0};
        this._index = null;
        this._indexFields = {
            passwords: ['website', 'username', 'url', 'type', 'password', 'notes', 'label', 'id', 'revision', 'edited', 'status', 'statusCode', 'favorite', 'sseType', 'cseType'],
            folders  : ['label', 'type', 'id', 'revision', 'edited', 'sseType', 'cseType'],
            tags     : ['label', 'type', 'id', 'revision', 'edited', 'sseType', 'cseType']
        };
        this._domIdentifiers = {
            passwords: 'data-password-id',
            folders  : 'data-folder-id',
            tags     : 'data-tag-id'
        };
        this._exactMatchFields = ['status', 'favorite'];
        this._aliasFields = {name: 'label', title: 'label', colour: 'color', favourite: 'favorite', user: 'username', all: ['website', 'username', 'url', 'notes', 'label']};
    }

    init() {
        if(OC.Plugins) {
            if(SettingsManager.get('server.version') > 13) {
                new OCA.Search((q) => {this.search(q);}, () => {this.search();});
            } else {
                OC.Plugins.register('OCA.Search', this);
            }
            document.querySelector('form.searchbox').style.opacity = '0';
            this._status.available = false;

            this._initializeSearchFeatures();
        }
    }

    // noinspection JSUnusedGlobalSymbols
    attach(search) {
        search.setFilter('passwords', (q) => { this.search(q); });
    }

    search(query) {
        if(query === undefined || query.trim().length === 0) {
            this._resetSearch();
            return;
        }

        let stats        = {passwords: 0, folders: 0, tags: 0, start: new Date().getTime()},
            searchParams = this._processQuery(query),
            index        = this._getSearchIndex();
        for(let key in index) {
            if(!index.hasOwnProperty(key)) continue;
            let section    = index[key],
                identifier = this._domIdentifiers[key];

            for(let i = 0; i < section.length; i++) {
                let object = section[i],
                    el     = document.querySelector(`[${identifier}="${object.id}"]`);
                if(!el) continue;

                if(this._checkIfObjectMatchesQuery(object, searchParams)) {
                    if(el.classList.contains('search-hidden')) el.classList.remove('search-hidden');
                    el.classList.add('search-visible');
                    stats[key]++;
                } else {
                    if(el.classList.contains('search-visible')) el.classList.remove('search-visible');
                    el.classList.add('search-hidden');
                }
            }
        }
        this._updateStatus(query, stats);
    }

    /**
     *
     * @param query
     * @param stats
     * @private
     */
    _updateStatus(query, stats) {
        this._status.active = true;
        this._status.query = query;
        this._status.total = stats.passwords + stats.folders + stats.tags;
        this._status.passwords = stats.passwords;
        this._status.folders = stats.folders;
        this._status.tags = stats.tags;
        this._status.time = new Date().getTime() - stats.start;
    }

    /**
     *
     * @param entry
     * @param query
     * @returns {boolean}
     * @private
     */
    _checkIfObjectMatchesQuery(entry, query) {
        queryLoop: for(let j = 0; j < query.length; j++) {
            let fields = query[j].fields,
                search = query[j].value;

            for(let k = 0; k < fields.length; k++) {
                let field = fields[k];
                if(!entry.hasOwnProperty(field)) continue;

                if(this._exactMatchFields.indexOf(search) !== -1 && entry[field] === search) {
                    continue queryLoop;
                }

                if(entry[field].indexOf(search) !== -1) continue queryLoop;
            }
            return false;
        }
        return true;
    }

    /**
     * Clears the search database
     */
    clearDatabase() {
        this._db = {};
        this._index = null;
        this._status.query = '';
        this._resetSearch();

        document.querySelector('form.searchbox').style.opacity = '0';
        document.getElementById('searchbox').value = '';
    }

    /**
     * Update the search database
     *
     * @param database
     */
    setDatabase(database) {
        this._db = database;
        this._index = null;
        this._resetSearch();

        if(database.passwords.length || database.folders.length || database.tags.length) {
            document.querySelector('form.searchbox').style.opacity = '1';
            document.getElementById('searchbox').value = '';
            this._status.available = true;
            setTimeout(() => {this.search(this._status.query);}, 1);
        }
    }

    /**
     *
     * @private
     */
    _resetSearch() {
        this._status.active = false;
        let elements = document.querySelectorAll('.search-hidden, .search-visible');

        elements.forEach((el) => {
            el.classList.remove('search-hidden');
            el.classList.remove('search-visible');
        });
    }

    /**
     *
     * @param query
     * @returns {string}
     * @private
     */
    _processQuery(query) {
        let isQuoted  = false,
            value     = '',
            substring = '',
            field     = 'all',
            params    = [];

        query = query.toLowerCase();
        for(let i = 0; i < query.length; i++) {
            let char = query[i];

            if(!isQuoted && char === ':' && substring.length !== 0) {
                this._addFieldToSearchParams(params, field, value);

                field = substring;
                substring = '';
                value = '';
            } else if(char === ' ') {
                value += `${substring} `;
                substring = '';
            } else if(char === '"') {
                if(value.length !== 0 || substring.length !== 0) {
                    this._addFieldToSearchParams(params, field, value + substring);
                    substring = '';
                    field = 'all';
                    value = '';
                }
                isQuoted = !isQuoted;
            } else if(isQuoted && char === '\\' && query[i + 1] === '"') {
                substring += '"';
                i++;
            } else {
                substring += char;
            }
        }
        if(substring.length !== 0) value += substring;
        if(value.length !== 0) this._addFieldToSearchParams(params, field, value);

        return params;
    }

    /**
     *
     * @param params
     * @param field
     * @param rawValue
     * @private
     */
    _addFieldToSearchParams(params, field, rawValue) {
        if(this._aliasFields.hasOwnProperty(field)) field = this._aliasFields[field];

        let fields = Array.isArray(field) ? field:[field],
            value = rawValue.trim();

        if(value.length !== 0) params.push({fields, value});
    }

    /**
     *
     * @returns {null|{}}
     * @private
     */
    _getSearchIndex() {
        if(this._index !== null) return this._index;

        this._index = {};
        for(let key in this._db) {
            if(!this._db.hasOwnProperty(key)) continue;
            let section = this._db[key],
                fields  = this._indexFields[key];

            this._index[key] = [];
            for(let i = 0; i < section.length; i++) {
                let object        = section[i],
                    indexedObject = {};

                for(let j = 0; j < fields.length; j++) {
                    let field = fields[j];

                    if(object.hasOwnProperty(field)) {
                        let type = typeof object[field];
                        if(object[field] instanceof Date) {
                            indexedObject[field] = Math.floor(object[field].getTime() / 1000);
                        } else if(type === 'boolean') {
                            indexedObject[field] = object[field] ? '1':'0';
                        } else {
                            indexedObject[field] = object[field].toString().toLowerCase();
                        }
                    } else {
                        indexedObject[field] = '';
                    }
                }
                this._index[key].push(indexedObject);
            }
        }

        return this._index;
    }

    /**
     * Initialize optional search features
     *
     * @private
     */
    _initializeSearchFeatures() {
        this._globalSearch();
        this._initLiveSearch();
    }

    /**
     * Search globally when the user presses Enter
     *
     * @private
     */
    _globalSearch() {
        document.getElementById('searchbox').addEventListener('keyup', (e) => {
            if(e.key === 'Enter' && router.history.current.name !== 'Search' && SettingsManager.get('client.search.global')) {
                router.push({name: 'Search', params: {query: btoa(SM.status.query)}});
            }
        });
    }

    /**
     * Search when the user presses a key
     *
     * @private
     */
    _initLiveSearch() {
        let searchbox = document.getElementById('searchbox');

        document.addEventListener('keypress', (e) => {
            if(!this._status.available) return;
            if(e.ctrlKey || e.altKey || e.shiftKey || e.metaKey || e.repeat) return;
            if(['INPUT', 'TEXTAREA'].indexOf(e.target.nodeName) !== -1) return;
            if(!SettingsManager.get('client.search.live')) return;

            if(/^[a-zA-Z0-9-_ ]{1}$/.test(e.key)) {
                searchbox.value += e.key;
                searchbox.focus();
                this.search(searchbox.value);
            }
        });
    }
}

let SM = new SearchManager();

export default SM;