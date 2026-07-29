/*
 * @copyright 2026 Passwords App
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the Passwords App
 * created by Marius David Wieschollek.
 */

import {getCurrentUser} from "@nextcloud/auth";
import API from "@js/Helper/api";
import CreateShareAction from "@js/Actions/Share/CreateShareAction";
import CreateShareError from "@js/Actions/Share/CreateShareError";
import LoggingService from "@js/Services/LoggingService";

export default class CreateGroupShareAction {
    #password;
    #options;
    #group;
    #user;

    constructor(password, group, options) {
        this.#password = password;
        this.#group = group;
        this.#options = options;
        this.#user = getCurrentUser();
    }

    async run() {
        let messages = [],
            promises = [];
        const users = await API.resolveGroup(this.#group.id);

        for(let userId in users) {
            if(users.hasOwnProperty(userId) && userId !== this.#user.uid) {
                const recipient = {id: userId, displayName: users[userId]};

                promises.push(
                    this.#createShare(recipient, messages)
                );
            }
        }

        await Promise.all(promises);

        return messages;
    }

    async #createShare(receiver, messages) {
        try {
            let action = new CreateShareAction(
                this.#password,
                receiver,
                this.#options
            );

            await action.run();
        } catch(e) {
            if(e instanceof CreateShareError) {
                messages.push(e.message);
            } else {
                LoggingService.error(e);
            }
        }
    }
}
