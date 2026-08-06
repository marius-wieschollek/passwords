/*
 * @copyright 2026 Passwords App
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the Passwords App
 * created by Marius David Wieschollek.
 */

import CreateShareAction from "@js/Actions/Share/CreateShareAction";
import CreateShareError from "@js/Actions/Share/CreateShareError";
import LoggingService from "@js/Services/LoggingService";
import LocalisationService from "@js/Services/LocalisationService";

/**
 * Creates a single share for a group.
 * The server expands it into one share per group member and keeps
 * that list in sync with the members of the group.
 */
export default class CreateGroupShareAction {
    #password;
    #options;
    #group;

    constructor(password, group, options) {
        this.#password = password;
        this.#group = group;
        this.#options = options;
    }

    async run() {
        let messages = [];

        try {
            let action = new CreateShareAction(
                this.#password,
                {id: this.#group.id, displayName: this.#group.displayName, type: 'group'},
                this.#options
            );

            await action.run();
        } catch(e) {
            if(e instanceof CreateShareError) {
                messages.push(e.message);
            } else {
                // The error is reported as well, otherwise the user is told that everything worked.
                // CreateShareError translates its message, so these have to be translated too
                LoggingService.error(e);
                let message = e.hasOwnProperty('message') ? e.message:e.statusText;
                messages.push(LocalisationService.translateArray(['Unable to share password: {message}', {message}]));
            }
        }

        return messages;
    }
}
