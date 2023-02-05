/*
 * @copyright 2023 Passwords App
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the Passwords App
 * created by Marius David Wieschollek.
 */

import PrintPasswordAction from "@js/Actions/Password/PrintPasswordAction";
import PasswordManager from "@js/Manager/PasswordManager";
import Vue from "vue";
import Utility from "@js/Classes/Utility";
import API from '@js/Helper/api';
import Messages from "@js/Classes/Messages";
import AddTagAction from "@js/Actions/Password/AddTagAction";
import Localisation from "@js/Classes/Localisation";
import Logger from "@js/Classes/Logger";

export default class PasswordActions {
    get password() {
        return this._password;
    }

    constructor(password) {
        this._password = password;
    }

    print() {
        let printer = new PrintPasswordAction(this._password);
        printer.print().catch(Logger.exception);
    }

    async favorite(status = null) {
        let oldStatus = this._password.favorite === true;
        if(status !== null) {
            this._password.favorite = status === true;
        } else {
            this._password.favorite = !this._password.favorite;
        }

        try {
            await PasswordManager.updatePassword(this._password);
        } catch(e) {
            this._password.favorite = oldStatus;
            Logger.error(e);
        }

        return this._password;
    }

    edit() {
        return PasswordManager.editPassword(this._password);
    }

    clone() {
        return PasswordManager.clonePassword(this._password);
    }

    delete() {
        return PasswordManager.deletePassword(this._password);
    }

    move(folder = null) {
        return PasswordManager.movePassword(this._password, folder);
    }

    async addTag(tag) {
        let action = new AddTagAction(this._password);
        this._password = await action.addTag(tag);
        return this._password;
    }

    async qrcode() {
        let PasswordQrCode = await import(/* webpackChunkName: "QrCode" */ '@vue/Dialog/QrCode.vue'),
            PwQrCodeDialog = Vue.extend(PasswordQrCode.default);

        new PwQrCodeDialog({propsData: {password: this._password}}).$mount(Utility.popupContainer());
    }

    clipboard(attribute) {
        let message = 'Error copying {element} to clipboard';
        if(!this._password.hasOwnProperty(attribute) || this._password[attribute].length === 0) {
            message = 'ClipboardCopyEmpty';
        } else {
            if(Utility.copyToClipboard(this._password[attribute])) message = '{element} was copied to clipboard';
        }

        Messages.notification([message, {element: Localisation.translate(attribute.capitalize())}]);
    }
}