/*
 * @copyright 2026 Passwords App
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the Passwords App
 * created by Marius David Wieschollek.
 */

import BatchAction from "@js/Actions/BatchActions/BatchAction";
import PasswordManager from "@js/Manager/PasswordManager";
import FolderManager from "@js/Manager/FolderManager";
import TagManager from "@js/Manager/TagManager";
import ToastService from "@js/Services/ToastService";

export default class RestoreTrashItemsBatchAction extends BatchAction {

    async run() {
        if(!await MessageService.confirm('Restore all items in trash?', 'Restore Items', true)) {
            return;
        }

        await this.#restoreItems();

        ToastService.success(['BatchActionRestoreToast', {total: this.count()}]);
    }


    async #restoreItems() {
        let promises = [];
        for(let folder of this._items.folders) {
            promises.push(FolderManager.restoreFolder(folder));
        }
        await Promise.all(promises);

        promises = [];
        for(let tag of this._items.tags) {
            promises.push(TagManager.restoreTag(tag));
        }
        await Promise.all(promises);


        promises = [];
        for(let password of this._items.passwords) {
            promises.push(PasswordManager.restorePassword(password));
        }
        await Promise.all(promises);
    }
}