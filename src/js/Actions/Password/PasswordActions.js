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
import AddTagAction from "@js/Actions/Password/AddTagAction";
import Events from "@js/Classes/Events";
import ToastService from "@js/Services/ToastService";
import UtilityService from "@js/Services/UtilityService";
import LocalisationService from "@js/Services/LocalisationService";
import LoggingService from "@js/Services/LoggingService";

export default class PasswordActions {
    #password;

    get password() {
        return this.#password;
    }

    constructor(password) {
        this.#password = password;
        Events.on('password.changed', (event) => {
            if(this.#password.id === event.object.id) {
                this.#password = event.object;
            }
        })
    }

    print() {
        let printer = new PrintPasswordAction(this.#password);
        printer.print().catch(LoggingService.exception);
    }

    async favorite(status = null) {
        let oldStatus = this.#password.favorite === true;
        if(status !== null) {
            this.#password.favorite = status === true;
        } else {
            this.#password.favorite = !this.#password.favorite;
        }

        try {
            await PasswordManager.updatePassword(this.#password);
        } catch(e) {
            this.#password.favorite = oldStatus;
            LoggingService.error(e);
        }

        return this.#password;
    }

    edit() {
        return PasswordManager.editPassword(this.#password);
    }

    clone() {
        return PasswordManager.clonePassword(this.#password);
    }

    delete() {
        return PasswordManager.deletePassword(this.#password);
    }

    move(folder = null) {
        return PasswordManager.movePassword(this.#password, folder);
    }

    async addTag(tag) {
        let action = new AddTagAction(this.#password);
        this.#password = await action.addTag(tag);
        return this.#password;
    }

    async qrcode() {
        let PasswordQrCode = await import(/* webpackChunkName: "QrCode" */ '@vue/Dialog/QrCode.vue'),
            PwQrCodeDialog = Vue.extend(PasswordQrCode.default);

        new PwQrCodeDialog({propsData: {password: this.#password}}).$mount(UtilityService.popupContainer());
    }

    async openChangePasswordPage() {
        let ChangePasswordPage = await import(/* webpackChunkName: "ChangePasswordPage" */ '@vue/Dialog/ChangePasswordPage.vue'),
            ChangePasswordPageDialog = Vue.extend(ChangePasswordPage.default);

        new ChangePasswordPageDialog({propsData: {password: this.#password}}).$mount(UtilityService.popupContainer());
    }

    clipboard(attribute) {
        let message = 'Error copying {element} to clipboard';
        if(!this.#password.hasOwnProperty(attribute) || this.#password[attribute].length === 0) {
            message = 'ClipboardCopyEmpty';
        } else {
            if(UtilityService.copyToClipboard(this.#password[attribute])) message = '{element} was copied to clipboard';
        }

        ToastService.info([message, {element: LocalisationService.translate(attribute.capitalize())}]);
    }
}