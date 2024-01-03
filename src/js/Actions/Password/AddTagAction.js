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
import PasswordManager from "@js/Manager/PasswordManager";
import Logger from "@js/Classes/Logger";
import ToastService from "@js/Services/ToastService";
import UtilityService from "@js/Services/UtilityService";

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

            for(let pwTag of UtilityService.objectToArray(this._password.tags)) {
                if(pwTag.id === tag.id) {
                    ToastService.warning(['PasswordTagAddExists', {password: this._password.label, tag: tag.label}]);
                    return;
                }
            }

            this._password.tags[tag.id] = tag;
            this._password = await PasswordManager.updatePassword(this._password);
            ToastService.success(['PasswordTagAddSuccess', {password: this._password.label, tag: tag.label}]);
        } catch(e) {
            Logger.error(e);
            ToastService.error(['PasswordTagAddFail', {password: this._password.label, tag: tag.label, error: e.hasOwnProperty('message') ? e.message:''}]);
        }

        return this._password;
    }
}