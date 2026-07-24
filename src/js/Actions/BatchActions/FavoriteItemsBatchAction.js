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
        let makeFavorite = this._options.favorite;

        let promises = [];
        for(let password of this._items.passwords) {
            if(password.favorite === makeFavorite) continue;
            password.favorite = makeFavorite;
            promises.push(PasswordManager.updatePassword(password));
        }
        await Promise.all(promises);

        promises = [];
        for(let folder of this._items.folders) {
            if(folder.favorite === makeFavorite) continue;
            folder.favorite = makeFavorite;
            promises.push(FolderManager.updateFolder(folder));
        }
        await Promise.all(promises);

        promises = [];
        for(let tag of this._items.tags) {
            if(tag.favorite === makeFavorite) continue;
            tag.favorite = makeFavorite;
            promises.push(TagManager.updateTag(tag));
        }
        await Promise.all(promises);

        ToastService.success([makeFavorite ? 'BatchActionFavoriteAddedToast':'BatchActionFavoriteRemoveToast', {total: this.count()}]);
    }
}