/*
 * @copyright 2026 Passwords App
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the Passwords App
 * created by Marius David Wieschollek.
 */

import TagManager from "@js/Manager/TagManager";
import BatchAction from "@js/Actions/BatchActions/BatchAction";

export default class ManageTagsBatchAction extends BatchAction {

    get count() {
        return this._items.passwords.length;
    }

    hasItem(item) {
        return this._items.passwords.indexOf(item) !== -1;
    }

    async run() {
        await TagManager.manageTags(this._items.passwords);
    }
}