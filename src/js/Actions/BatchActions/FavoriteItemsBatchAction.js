/*
 * @copyright 2026 Passwords App
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the Passwords App
 * created by Marius David Wieschollek.
 */

import PasswordManager from "@js/Manager/PasswordManager";
import BatchAction from "@js/Actions/BatchActions/BatchAction";
import FolderManager from "@js/Manager/FolderManager";
import TagManager from "@js/Manager/TagManager";
import ToastService from "@js/Services/ToastService";

export default class FavoriteItemsBatchAction extends BatchAction {

    async run() {
        const makeFavorite = this._options.favorite;

        let total = 0;

        total += await this.#setFavoriteStatus(
            this._items.passwords,
            makeFavorite,
            async (password) => { await PasswordManager.updatePassword(password); }
        );
        total += await this.#setFavoriteStatus(
            this._items.folders,
            makeFavorite,
            async (folder) => { await FolderManager.updateFolder(folder); }
        );
        total += await this.#setFavoriteStatus(
            this._items.tags,
            makeFavorite,
            async (tag) => { await TagManager.updateTag(tag); }
        );

        ToastService.success([makeFavorite ? 'BatchActionFavoriteAddedToast':'BatchActionFavoriteRemoveToast', {total}]);
    }

    async #setFavoriteStatus(items, state, callback) {
        let total    = 0,
            promises = [];

        for(let item of items) {
            if(item.favorite === state) continue;
            item.favorite = state;
            promises.push(callback(item));
            total++;
        }
        await Promise.all(promises);

        return total;
    }
}