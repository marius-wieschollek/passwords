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
import TagManager from "@js/Manager/TagManager";
import LoggingService from "@js/Services/LoggingService";

export default class TagActions {
    #tag;

    get tag() {
        return this.#tag;
    }

    constructor(tag) {
        this.#tag = tag;
        Events.on('tag.changed', (event) => {
            if(this.tag.id === event.object.id) {
                this.#tag = event.object;
            }
        });
    }

    async favorite(status = null) {
        let oldStatus = this.#tag.favorite === true;
        if(status !== null) {
            this.#tag.favorite = status === true;
        } else {
            this.#tag.favorite = !this.#tag.favorite;
        }

        try {
            await TagManager.updateTag(this.#tag);
        } catch(e) {
            this.#tag.favorite = oldStatus;
            LoggingService.error(e);
        }

        return this.#tag;
    }

    delete() {
        return TagManager.deleteTag(this.#tag);
    }

    edit() {
        return TagManager.editTag(this.#tag);
    }
}