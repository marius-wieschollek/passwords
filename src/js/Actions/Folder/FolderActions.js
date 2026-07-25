/*
 * @copyright 2026 Passwords App
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the Passwords App
 * created by Marius David Wieschollek.
 */

import Events from "@js/Classes/Events";
import FolderManager from "@js/Manager/FolderManager";
import LoggingService from "@js/Services/LoggingService";

export default class FolderActions {
    #folder;

    get folder() {
        return this.#folder;
    }

    constructor(folder) {
        this.#folder = folder;
        Events.on('folder.changed', (event) => {
            if(this.folder.id === event.object.id) {
                this.#folder = event.object;
            }
        });
    }

    async favorite(status = null) {
        let oldStatus = this.#folder.favorite === true;
        if(status !== null) {
            this.#folder.favorite = status === true;
        } else {
            this.#folder.favorite = !this.#folder.favorite;
        }

        try {
            await FolderManager.updateFolder(this.#folder);
        } catch(e) {
            this.#folder.favorite = oldStatus;
            LoggingService.error(e);
        }

        return this.#folder;
    }

    delete() {
        return FolderManager.deleteFolder(this.#folder);
    }

    move(target = null) {
        return FolderManager.moveFolder(this.#folder, target);
    }

    rename() {
        return FolderManager.renameFolder(this.#folder);
    }
}