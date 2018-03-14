class SearchManager {

    constructor() {
        this.db = {};
        this.index = null;
        this.indexFields = {
            passwords: ['website', 'username', 'url', 'type', 'password', 'notes', 'label', 'id'],
            folders  : ['label', 'type', 'id'],
            tags     : ['label', 'type', 'id']
        };
        this.domIdentifiers = {
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
        if(query === undefined || query.length === 0) {
            SearchManager._resetSearch();
            return;
        }

        let stats        = {query: query, results: 0, passwords: 0, folders: 0, tags: 0, start: new Date().getTime()},
            searchParams = SearchManager._processQuery(query),
            index        = this._getSearchIndex();
        for(let key in index) {
            if(!index.hasOwnProperty(key)) continue;
            let section    = index[key],
                identifier = this.domIdentifiers[key];

            for(let i = 0; i < section.length; i++) {
                let object = section[i],
                    el     = document.querySelector('[' + identifier + '="' + object.id + '"]');
                if(!el) continue;

                if(SearchManager._entryMatchesQuery(object, searchParams)) {
                    if(el.classList.contains('search-hidden')) el.classList.remove('search-hidden');
                    stats.results++;
                    stats[key]++;
                } else {
                    el.classList.add('search-hidden');
                }
            }
        }
        console.log(stats.results + ' results in ' + (new Date().getTime() - stats.start) + ' milliseconds');
    }

    /**
     *
     * @param entry
     * @param query
     * @returns {boolean}
     * @private
     */
    static _entryMatchesQuery(entry, query) {
        for(let j = 0; j < query.length; j++) {
            let fields = query[j].field,
                search = query[j].value;

            if(fields === 'all') {
                fields = ['website', 'username', 'url', 'notes', 'label'];
            } else if(fields === 'name' || fields === 'title') {
                fields = ['label'];
            } else {
                fields = [fields];
            }

            for(let k = 0; k < fields.length; k++) {
                let field = fields[k];
                if(!entry.hasOwnProperty(field)) continue;

                if(entry[field].indexOf(search) === -1) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Clears the search database
     */
    clearDatabase() {
        this.db = {};
        this.index = null;

        document.querySelector('form.searchbox').style.opacity = '0';
        document.getElementById('searchbox').value = '';
    }

    /**
     * Update the search database
     *
     * @param database
     */
    setDatabase(database) {
        this.db = database;
        this.index = null;
        document.querySelector('form.searchbox').style.opacity = '1';
        document.getElementById('searchbox').value = '';
    }

    /**
     *
     * @private
     */
    static _resetSearch() {
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
                value += substring + ' ';
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

        console.log(params);
        return params;
    }

    static _addFieldToSearchParams(params, field, value) {
        let realValue = value.trim();
        if(realValue.length > 2) params.push({field: field, value: realValue});
    }

    /**
     *
     * @returns {null|{}}
     * @private
     */
    _getSearchIndex() {
        if(this.index !== null) return this.index;

        this.index = {};
        for(let key in this.db) {
            if(!this.db.hasOwnProperty(key)) continue;
            let section = this.db[key],
                fields  = this.indexFields[key];

            this.index[key] = [];
            for(let i = 0; i < section.length; i++) {
                let object        = section[i],
                    indexedObject = {};

                for(let j = 0; j < fields.length; j++) {
                    let field = fields[j];

                    indexedObject[field] = object.hasOwnProperty(field) ? object[field].toLowerCase():'';
                }
                this.index[key].push(indexedObject);
            }
        }

        return this.index;
    }
}

let SM = new SearchManager();

export default SM;