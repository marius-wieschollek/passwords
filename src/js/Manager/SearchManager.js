class SearchManager {

    get status() {
        return this._status;
    }

    constructor() {
        this._db = {};
        this._status = {active: false, query: '', total: 0, passwords: 0, folders: 0, tags: 0, time: 0};
        this._index = null;
        this._indexFields = {
            passwords: ['website', 'username', 'url', 'type', 'password', 'notes', 'label', 'id'],
            folders  : ['label', 'type', 'id'],
            tags     : ['label', 'type', 'id']
        };
        this._domIdentifiers = {
            passwords: 'data-password-id',
            folders  : 'data-folder-id',
            tags     : 'data-tag-id'
        };
    }

    init() {
        if(OC.Plugins) {
            OC.Plugins.register('OCA.Search', this);
            document.querySelector('form.searchbox').style.opacity = '0';
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
            searchParams = SearchManager._processQuery(query),
            index        = this._getSearchIndex();
        for(let key in index) {
            if(!index.hasOwnProperty(key)) continue;
            let section    = index[key],
                identifier = this._domIdentifiers[key];

            for(let i = 0; i < section.length; i++) {
                let object = section[i],
                    el     = document.querySelector(`[${identifier}="${object.id}"]`);
                if(!el) continue;

                if(SearchManager._entryMatchesQuery(object, searchParams)) {
                    if(el.classList.contains('search-hidden')) el.classList.remove('search-hidden');
                    stats[key]++;
                } else {
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
    static _entryMatchesQuery(entry, query) {
        queryLoop: for(let j = 0; j < query.length; j++) {
            let fields = query[j].field,
                search = query[j].value;

            if(fields === 'all') {
                fields = ['website', 'username', 'url', 'notes', 'label'];
            } else if(fields === 'name' || fields === 'title') {
                fields = ['label'];
            } else {
                fields = [fields];
            }

            let entryMatches = false;
            for(let k = 0; k < fields.length; k++) {
                let field = fields[k];
                if(!entry.hasOwnProperty(field)) continue;

                if(entry[field].indexOf(search) !== -1) {
                    continue queryLoop;
                }
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

        document.querySelector('form.searchbox').style.opacity = '1';
        document.getElementById('searchbox').value = '';
    }

    /**
     *
     * @private
     */
    _resetSearch() {
        this._status.active = false;
        let elements = document.querySelectorAll('.search-hidden');

        elements.forEach((el) => {
            el.classList.remove('search-hidden');
        });
    }

    /**
     *
     * @param query
     * @returns {string}
     * @private
     */
    static _processQuery(query) {
        let isQuoted  = false,
            value     = '',
            substring = '',
            field     = 'all',
            params    = [];

        query = query.toLowerCase();
        for(let i = 0; i < query.length; i++) {
            let char = query[i];

            if(!isQuoted && char === ':' && substring.length !== 0) {
                SearchManager._addFieldToSearchParams(params, field, value);

                field = substring;
                substring = '';
                value = '';
            } else if(char === ' ') {
                value += `${substring} `;
                substring = '';
            } else if(char === '"') {
                if(value.length !== 0 || substring.length !== 0) {
                    SearchManager._addFieldToSearchParams(params, field, value + substring);
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
        if(value.length !== 0) SearchManager._addFieldToSearchParams(params, field, value);

        return params;
    }

    /**
     *
     * @param params
     * @param field
     * @param rawValue
     * @private
     */
    static _addFieldToSearchParams(params, field, rawValue) {
        let value = rawValue.trim();
        params.push({field, value});
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

                    indexedObject[field] = object.hasOwnProperty(field) ? object[field].toLowerCase():'';
                }
                this._index[key].push(indexedObject);
            }
        }

        return this._index;
    }
}

let SM = new SearchManager();

export default SM;