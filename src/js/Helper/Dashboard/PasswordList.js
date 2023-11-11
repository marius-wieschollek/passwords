/*
 * @copyright 2023 Passwords App
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the Passwords App
 * created by Marius David Wieschollek.
 */

import Utility from "@js/Classes/Utility";
import SettingsService from "@js/Services/SettingsService";

export default class PasswordList {

    get ready() {
        return this._ready;
    }

    get mode() {
        return this._query === null ? 'favorites':'search';
    }

    get query() {
        return this._query;
    }

    get passwords() {
        return this._passwords;
    }

    /**
     *
     * @param {EnhancedApi} api
     */
    constructor(api) {
        this._passwords = [];
        this._passwordList = [];
        this._query = null;
        this._oldQuery = null;
        this._interval = null;
        this._ready = false;
        this._api = api;
    }

    init() {
        this._loadFavorites();
        this._loadAllPasswords();
        this._interval = setInterval(
            () => {this._loadAllPasswords();},
            60000
        );
    }

    stop() {
        if(this._interval !== null) {
            clearInterval(this._interval);
        }
        this._passwords = [];
        this._passwordList = [];
        this._query = null;
        this._oldQuery = null;
        this._ready = false;
    }

    _loadAllPasswords() {
        this._api.findPasswords()
            .then((passwords) => {
                let data = Utility.objectToArray(Utility.sortApiObjectArray(passwords, this._getPasswordsSortingField()));
                if(data.length > 0) {
                    this._passwordList = data;
                    this._updateSearchResults();
                    this._ready = true;
                }
            });
    }

    _loadFavorites() {
        this._api.findPasswords({favorite: true})
            .then((passwords) => {
                let data = Utility.objectToArray(Utility.sortApiObjectArray(passwords, this._getPasswordsSortingField()));
                if(data.length > 0 && this._passwordList.length === 0) {
                    this._passwordList = data;
                    this._updateSearchResults();
                    this._ready = true;
                }
            });
    }

    search(query) {
        if(query.length < 3) {
            query = null;
            this._oldQuery = null;
        }

        if(this._query !== query) {
            this._query = query;
            this._updateSearchResults();
        }
    }

    _updateSearchResults() {
        if(this._query === null) {
            this._findFavorites();
            return;
        }

        this._findQueryResults();
    }

    _findFavorites() {
        let favorites = [];
        for(let password of this._passwordList) {
            if(password.favorite) {
                favorites.push(password);
            }
        }

        if(favorites.length > 0) {
            this._overwriteArray(this._passwords, favorites);
        } else if(this._passwordList.length > 0) {
            this._overwriteArray(this._passwords, this._passwordList.slice(0, 10));
        } else {
            this._overwriteArray(this._passwords, []);
        }
    }

    _findQueryResults() {
        let results  = [],
            query    = this._query,
            database = this._passwordList;

        /**
         * If the query starts with the old query,
         * we don't need to search everything.
         * just the results from last time.
         */
        if(this._oldQuery !== null && query.indexOf(this._oldQuery) === 0) {
            database = this._passwords;
        }

        for(let password of database) {
            let matches = 0;
            for(let field of ['label', 'username', 'notes', 'url']) {
                matches += this._countOccurrencesInString(password[field], query);
            }


            for(let field of password.customFields) {
                if(field.type !== 'secret' && field.type !== 'data' && field.type !== 'file') {
                    matches += this._countOccurrencesInString(field.value, query);
                }
            }

            if(matches > 0) {
                results.push({matches, password});
            }
        }

        results.sort((a, b) => {
            let result = b.matches - a.matches;
            if(result === 0 && a.password.favorite !== b.password.favorite) {
                return a.password.favorite ? -1:1;
            }

            return result;
        });

        /**
         * If the search takes too long, the query may have changed
         */
        if(query === this._query) {
            this._oldQuery = query;
            this._overwriteArray(this._passwords, []);
            for(let result of results) {
                this._passwords.push(result.password);
            }
        }
    }

    _countOccurrencesInString(string, search) {
        if(search.length > string.length || search.length === 0) return 0;

        let matches  = 0,
            position = 0;

        while(true) {
            position = string.indexOf(search, position);
            if(position >= 0) {
                matches++;
                position += search.length;
            } else {
                break;
            }
        }
        return matches;
    }

    _overwriteArray(array, contents) {
        while(array.pop() !== undefined) {}

        for(let item of contents) {
            array.push(item);
        }
    }


    _getPasswordsSortingField() {
        let sortingField = SettingsService.get('client.ui.password.field.sorting');
        if(sortingField === 'byTitle') sortingField = SettingsService.get('client.ui.password.field.title');
        return sortingField;
    }
}