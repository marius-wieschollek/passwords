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

export default class PasswordActions {
    get password() {
        return this._password;
    }

    constructor(password) {
        this._password = password;
    }

    print() {
        let printer = new PrintPasswordAction(this._password);
        printer.print().catch(console.error);
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
            this._password.favorite = oldStatus
            console.error(e);
        }

        return this._password;
    }
    async edit() {
        return await PasswordManager.editPassword(this._password);
    }
    async clone() {
        return await PasswordManager.clonePassword(this._password);
    }

    async qrcode() {
        let PasswordQrCode = await import(/* webpackChunkName: "QrCode" */ '@vue/Dialog/QrCode.vue'),
            PwQrCodeDialog = Vue.extend(PasswordQrCode.default);

        new PwQrCodeDialog({propsData: {password: this._password}}).$mount(Utility.popupContainer());
    }
}