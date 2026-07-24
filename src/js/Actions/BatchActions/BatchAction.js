/*
 * @copyright 2026 Passwords App
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the Passwords App
 * created by Marius David Wieschollek.
 */

export default class BatchAction {
    _items;

    get clearSelection() {
        return false;
    }

    get count() {
        return this._items.folders.length +
               this._items.tags.length +
               this._items.passwords.length;
    }


    /**
     *
     * @param items {folders: Object, passwords: Object, tags: Object}
     * @param options Object
     */
    constructor(items, options = {}) {
        this._items = items;
        this._options = options;
    }

    hasItem(item) {
        return this._items.folders.indexOf(item) !== -1 ||
               this._items.tags.indexOf(item) !== -1 ||
               this._items.passwords.indexOf(item) !== -1;
    }

    async run() {
    }
}