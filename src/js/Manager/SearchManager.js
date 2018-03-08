class SearchManager {

    constructor() {
        this.db = {};
        this.index = null;
        this.indexFields = {
            passwords: ['website', 'username', 'url', 'type', 'password', 'notes', 'label', 'id'],
            folders  : ['label', 'id'],
            tags     : ['label', 'id']
        };
        this.domIdentifiers = {
            passwords: 'data-password-id',
            folders  : 'data-folder-id',
            tags     : 'data-tag-id'
        }
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

        let stats = {query: query, results: 0, passwords: 0, folders: 0, tags: 0, start: new Date().getTime()};
        query = SearchManager._processQuery(query);
        let index = this._getSearchIndex();
        for(let key in index) {
            if(!index.hasOwnProperty(key)) continue;
            let section    = index[key],
                identifier = this.domIdentifiers[key];

            objs: for(let i = 0; i < section.length; i++) {
                let object = section[i],
                    el     = document.querySelector('[' + identifier + '="' + object.id + '"]');
                if(!el) continue;

                for(let field in object) {
                    if(!object.hasOwnProperty(field)) continue;

                    if(object[field].indexOf(query) !== -1) {
                        if(el.classList.contains('search-hidden')) el.classList.remove('search-hidden');
                        stats.results++;
                        stats[key]++;
                        continue objs;
                    }
                }
                el.classList.add('search-hidden');
            }
        }
        console.log(stats.results + ' results in ' + (new Date().getTime() - stats.start) + ' milliseconds')
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
        return query.toLowerCase();
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
                this.index[key].push(indexedObject)
            }
        }

        return this.index;
    }
}

let SM = new SearchManager();

export default SM;