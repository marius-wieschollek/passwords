/*
 * @copyright 2026 Passwords App
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the Passwords App
 * created by Marius David Wieschollek.
 */

import UtilityService from "@js/Services/UtilityService";
import PasswordManager from "@js/Manager/PasswordManager";
import API from "@js/Helper/api";
import {getCurrentUser} from '@nextcloud/auth';
import CreateShareError from "@js/Actions/Share/CreateShareError";
import LoggingService from "@js/Services/LoggingService";
import {emitMultiple} from "@js/Helper/event-bus";

export default class CreateShareAction {
    #password;
    #options;
    #recipient;
    #user;
    #cseDisabled = false;

    constructor(password, recipient, options) {
        this.#password = password;
        this.#recipient = recipient;
        this.#options = options;
        this.#user = getCurrentUser();
    }

    /**
     * Whether the end-to-end encryption of the password was disabled.
     * This can not be undone, even if the share was not created afterwards.
     *
     * @returns {Boolean}
     */
    get cseDisabled() {
        return this.#cseDisabled;
    }

    async run() {
        await this.#loadShares();

        if(!this.#hasPermissionToShare()) {
            this.#throwError(['CreateShareErrorNoPermission', {password: this.#password.label, recipient: this.#recipient.displayName}]);
        }
        if(this.#isShareWithOwner()) {
            this.#throwError(['CreateShareErrorShareWithOwner', {password: this.#password.label, recipient: this.#recipient.displayName}]);
        }
        if(!await this.#clearExistingShares()) {
            this.#throwError(['CreateShareErrorAlreadyShared', {password: this.#password.label, recipient: this.#recipient.displayName}]);
        }

        await this.#disableCse();

        await this.#createShare();

        emitMultiple(['passwords:password:updated', 'passwords:item:updated'], this.#password);
    }

    #hasPermissionToShare() {
        return this.#recipient.id !== this.#user.uid &&
               (
                   !this.#password.hasOwnProperty('share') ||
                   this.#password.share === null ||
                   this.#password.share.shareable
               );
    }

    #isShareWithOwner() {
        return this.#password.hasOwnProperty('share') && this.#password.share !== null && this.#password.share.owner.id === this.#recipient.id;
    }

    async #loadShares() {
        if(this.#password.hasOwnProperty('shares') && (this.#password.share === null && typeof this.#password.share !== 'string')) {
            return;
        }

        this.#password = await API.showPassword(this.#password.id, 'model+shares');
    }

    async #clearExistingShares() {
        for(let index in this.#password.shares) {
            let share = this.#password.shares[index];

            // The shares of the members of a group share are managed by the server
            if(share.parentShare) continue;

            if(share.receiver.id === this.#recipient.id) {
                if(!this.#options.overwrite) {
                    return false;
                }

                await API.deleteShare(share.id);
                delete this.#password.shares[share.id];
            }
        }

        return true;
    }

    async #disableCse() {
        if(this.#password.cseType === 'none') return;

        let password = UtilityService.cloneObject(this.#password);
        password.shared = true;

        this.#password = await PasswordManager.updatePassword(password);
        this.#cseDisabled = true;
    }

    async #createShare() {
        let type  = this.#recipient.type === 'group' ? 'group':'user',
            share = {
                password : this.#password.id,
                expires  : this.#getExpiryDate(),
                editable : this.#getEditable(),
                shareable: this.#options.shareable,
                receiver : this.#recipient.id,
                type
            };

        let shareModel = await API.createShare(share);
        share.id = shareModel.id;
        share.updatePending = true;
        share.owner = {
            id  : this.#user.uid,
            name: this.#user.displayName
        };
        share.receiver = {id: this.#recipient.id, name: this.#recipient.displayName, type};

        this.#password.shares[share.id] = await API._processShare(share);
    }

    #getExpiryDate() {
        if(this.#password.share !== null &&
           this.#password.share.expires !== null &&
           (
               this.#options.expires === null ||
               this.#password.share.expires < this.#options.expires
           )
        ) {
            return this.#password.share.expires;
        }

        return this.#options.expires;
    }

    #getEditable() {
        if(this.#password.hasOwnProperty('share') && this.#password.share !== null) {
            return this.#password.share.editable;
        }

        return this.#password.editable && this.#options.editable;
    }

    #throwError(message) {
        let error = new CreateShareError(message);
        LoggingService.info(error.message);
        throw error;
    }
}