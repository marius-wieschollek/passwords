/*
 * @copyright 2026 Passwords App
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the Passwords App
 * created by Marius David Wieschollek.
 */

import API from "@js/Helper/api";
import {emitMultiple} from "@js/Helper/event-bus";

export default class DeleteShareAction {
    #password;
    #recipient;

    constructor(password, recipient) {
        this.#password = password;
        this.#recipient = recipient;

    }

    async run() {
        await this.#loadShares();
        await this.#clearShares();
    }

    async #loadShares() {
        if(this.#password.hasOwnProperty('shares') && (this.#password.share === null && typeof this.#password.share !== 'string')) {
            return;
        }

        this.#password = await API.showPassword(this.#password.id, 'model+shares');
    }

    async #clearShares() {
        for(let index in this.#password.shares) {
            let share = this.#password.shares[index];

            if(share.receiver.id === this.#recipient.id) {
                await API.deleteShare(share.id);
                delete this.#password.shares[index];
            }
        }

        emitMultiple(['passwords:password:updated', 'passwords:item:updated'], this.#password);
    }
}