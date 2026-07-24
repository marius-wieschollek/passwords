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
import TagManager from "@js/Manager/TagManager";
import FolderManager from "@js/Manager/FolderManager";
import ToastService from "@js/Services/ToastService";

export default class DeleteItemsBatchAction extends BatchAction {
    async run() {
        const languageTags = this.#getLanguageTags();

        if(!await MessageService.confirm([languageTags.text, {total: this.count()}], languageTags.title, true)) {
            return;
        }

        await this.#deleteItems();

        ToastService.success([languageTags.toast, {total: this.count()}]);
    }

    #getLanguageTags() {
        console.log(this._options.isTrashSection);
        if(this._options.isTrashSection) {
            return {
                title: 'Empty Trash',
                text : 'Delete all items in trash?',
                toast: 'Trash emptied'
            };
        }

        return {
            title: 'BatchActionDeleteItemsTitle',
            text : 'BatchActionDeleteItemsText',
            toast: 'BatchActionDeleteItemsToast'
        };
    }

    async #deleteItems() {
        let promises = [];
        for(let folder of this._items.folders) {
            promises.push(FolderManager.deleteFolder(folder, false));
        }
        await Promise.all(promises);

        promises = [];
        for(let tag of this._items.tags) {
            promises.push(TagManager.deleteTag(tag, false));
        }
        await Promise.all(promises);


        promises = [];
        for(let password of this._items.passwords) {
            promises.push(PasswordManager.deletePassword(password, false));
        }
        await Promise.all(promises);
    }
}