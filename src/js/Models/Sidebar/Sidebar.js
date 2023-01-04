/*
 * @copyright 2023 Passwords App
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the Passwords App
 * created by Marius David Wieschollek.
 */

export default class Sidebar {
    get title() {
        return this._title;
    }
    get tab() {
        return this._tab;
    }
    get item() {
        return this._item;
    }
    get type() {
        return this._type;
    }

    constructor(type, item, title, tab = null) {
        this._title = title;
        this._type = type;
        this._item = item;
        this._tab = tab;
    }
}