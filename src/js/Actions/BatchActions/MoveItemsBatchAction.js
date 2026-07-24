/*
 * @copyright 2026 Passwords App
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the Passwords App
 * created by Marius David Wieschollek.
 */

import FolderManager from "@js/Manager/FolderManager";
import PasswordManager from "@js/Manager/PasswordManager";
import BatchAction from "@js/Actions/BatchActions/BatchAction";
import ToastService from "@js/Services/ToastService";

export default class MoveItemsBatchAction extends BatchAction {

    get clearSelection() {
        return true;
    }

    async run() {
        let ignoredFolders = this._items.folders.map(folder => folder.id),
            targetFolder   = await FolderManager.selectFolder('00000000-0000-0000-0000-000000000000', ignoredFolders),
            targetFolderId = targetFolder.id;

        let promises = [];
        for(let folder of this._items.folders) {
            promises.push(FolderManager.moveFolder(folder, targetFolderId));
        }
        await Promise.all(promises);

        promises = [];
        for(let password of this._items.passwords) {
            promises.push(PasswordManager.movePassword(password, targetFolderId));
        }
        await Promise.all(promises);

        ToastService.success(['BatchActionMoveItemsToast', {total: this.count, folder: targetFolder.label}]);
    }
}