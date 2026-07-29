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
import UtilityService from "@js/Services/UtilityService";
import {spawnDialog} from '@nextcloud/vue/functions/dialog';
import MessageService from "@js/Services/MessageService";
import LoggingService from "@js/Services/LoggingService";
import CreateShareAction from "@js/Actions/Share/CreateShareAction";
import CreateShareError from "@js/Actions/Share/CreateShareError";
import DeleteShareAction from "@js/Actions/Share/DeleteShareAction";
import API from "@js/Helper/api";
import ToastService from "@js/Services/ToastService";
import CreateGroupShareAction from "@js/Actions/Share/CreateGroupShareAction";

export default class ShareBatchAction extends BatchAction {


    get count() {
        return this._items.passwords.length;
    }

    hasItem(item) {
        return this._items.passwords.indexOf(item) !== -1;
    }

    async run() {
        if(this._items.passwords.length < 1) {
            return;
        }

        let sharingOptions = await this.#showShareDialog();
        if(sharingOptions === null) {
            return;
        }

        if(sharingOptions.action === 'create') {
            let messages = await this.#createShares(sharingOptions);
            if(messages.length > 0) {
                MessageService.alert((await messages).join(' '));
            }
        } else if(sharingOptions.action === 'delete') {
            await this.#deleteShares(sharingOptions);
        }

        await this.#runSharingCron();

        ToastService.success(
            [
                sharingOptions.action === 'create' ? 'BatchActionShareCreatedToast':'BatchActionShareDeletedToast',
                {total: this.count, recipients: sharingOptions.recipients.length}
            ]
        );
    }

    #showShareDialog() {
        return new Promise(async (resolve) => {
            try {
                const BatchShareDialog     = await import(/* webpackChunkName: "BatchShareDialog" */ '@vue/Dialog/BatchActions/BatchShareDialog.vue'),
                      passwordsOnlyWarning = this._items.folders.length > 0 || this._items.tags.length > 0,
                      cseDisableWarning =  this._items.passwords.some(password => password.cseType !== 'none');

                spawnDialog(
                    BatchShareDialog.default,
                    {resolve, passwordsOnlyWarning, cseDisableWarning},
                    {container: UtilityService.popupContainer()}
                );
            } catch(e) {
                LoggingService.error(e);
                MessageService.alert(['Unable to load {module}', {module: 'BatchShareDialog'}], 'Network error');
                resolve(null);
            }
        });
    }

    async #createShares(sharingOptions) {
        let promises = [],
            messages = [];
        for(let recipient of sharingOptions.recipients) {
            let options = {
                expires  : sharingOptions.expires,
                editable : sharingOptions.permissions.edit,
                shareable: sharingOptions.permissions.share,
                overwrite: sharingOptions.overwrite
            };

            for(let password of this._items.passwords) {
                if(recipient.isNoUser) {
                    promises.push(
                        this.#createGroupShare(password, recipient, options, messages)
                    );
                } else {
                    promises.push(
                        this.#createShare(password, recipient, options, messages)
                    );
                }

            }
        }

        await Promise.all(promises);

        return messages;
    }

    async #createShare(password, receiver, options, messages) {
        try {
            let action = new CreateShareAction(
                password,
                receiver,
                options
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

    async #createGroupShare(password, receiver, options, messages) {
        try {
            let action = new CreateGroupShareAction(
                password,
                receiver,
                options
            );

            let newMessages = await action.run();
            messages.push(...newMessages);

        } catch(e) {
            LoggingService.error(e);
        }
    }

    async #deleteShares(sharingOptions) {
        let promises = [],
            messages = [];
        for(let recipient of sharingOptions.recipients) {
            for(let password of this._items.passwords) {
                let action = new DeleteShareAction(
                    password,
                    recipient
                );
                promises.push(action.run());
            }
        }

        await Promise.all(promises);

        return messages;
    }

    async #runSharingCron() {
        try {
            await API.runSharingCron();
        } catch(e) {
            LoggingService.error(e);
        }
    }
}