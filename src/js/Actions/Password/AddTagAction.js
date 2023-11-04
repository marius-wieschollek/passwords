/*
 * @copyright 2023 Passwords App
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the Passwords App
 * created by Marius David Wieschollek.
 */

import API from "@js/Helper/api";
import Utility from "@js/Classes/Utility";
import Messages from "@js/Classes/Messages";
import PasswordManager from "@js/Manager/PasswordManager";
import Logger from "@js/Classes/Logger";

export default class AddTagAction {

    /**
     *
     * @param {Object} password
     */
    constructor(password) {
        this._password = password;
    }

    async addTag(tag) {
        try {
            if(typeof tag === "string") {
                tag = await API.showTag(tag);
            }

            if(!this._password.tags) {
                let tagData = await API.showPassword(this._password.id, 'model+tags');
                this._password.tags = tagData.tags;
            }

            for(let pwTag of Utility.objectToArray(this._password.tags)) {
                if(pwTag.id === tag.id) {
                    Messages.notification(['PasswordTagAddExists', {password: this._password.label, tag: tag.label}]);
                    return;
                }
            }

            this._password.tags[tag.id] = tag;
            this._password = await PasswordManager.updatePassword(this._password);
            Messages.notification(['PasswordTagAddSuccess', {password: this._password.label, tag: tag.label}]);
        } catch(e) {
            Logger.error(e);
            Messages.notification(['PasswordTagAddFail', {password: this._password.label, tag: tag.label, error: e.hasOwnProperty('message') ? e.message:''}]);
        }

        return this._password;
    }
}