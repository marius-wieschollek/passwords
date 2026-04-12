/*
 * @copyright 2023 Passwords App
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the Passwords App
 * created by Marius David Wieschollek.
 */

import Vue from "vue";
import LocalisationService from "@js/Services/LocalisationService";
import ToastService from "@js/Services/ToastService";
import {DialogSeverity, getDialogBuilder, getFilePickerBuilder} from "@nextcloud/dialogs";
import UtilityService from "@js/Services/UtilityService";

export default new (class MessageService {
    /**
     *
     * @param notification
     */
    notification(notification) {
        return new Promise((resolve) => {
            ToastService.message(notification, {timeout: 10000}).then(resolve);
        });
    }

    /**
     *
     * @param message
     * @param title
     * @returns {Promise}
     */
    alert(message, title = "Alert") {
        return new Promise((resolve) => {
            message = LocalisationService.translateArray(message);
            title = LocalisationService.translateArray(title);

            getDialogBuilder(title)
                .setText(message)
                .setSeverity(DialogSeverity.Warning)
                .addButton({
                               label   : LocalisationService.translate("Ok"),
                               callback: resolve,
                               variant : "primary"
                           })
                .build()
                .show();
        });
    }

    /**
     *
     * @param message
     * @param title
     * @returns {Promise}
     */
    info(message, title = "Info") {
        return new Promise((resolve) => {
            message = LocalisationService.translateArray(message);
            title = LocalisationService.translateArray(title);

            getDialogBuilder(title)
                .setText(message)
                .setSeverity(DialogSeverity.Info)
                .addButton({
                               label   : LocalisationService.translate("Ok"),
                               callback: resolve,
                               variant : "primary"
                           })
                .build()
                .show();
        });
    }

    /**
     *
     * @param message
     * @param title
     * @param booleanResponse
     */
    confirm(message, title = "Confirm", booleanResponse = false) {
        return new Promise((resolve, reject) => {
            message = LocalisationService.translateArray(message);
            title = LocalisationService.translateArray(title);
            let callback = function(success) {
                if(booleanResponse) {
                    resolve(success === true);
                } else {
                    success ? resolve({}):reject({});
                }
            };

            getDialogBuilder(title)
                .setText(message)
                .setSeverity(DialogSeverity.Warning)
                .addButton({
                               label   : LocalisationService.translate("Cancel"),
                               callback: () => {
                                   callback(false);
                               },
                               variant : "error"
                           })
                .addButton({
                               label   : LocalisationService.translate("Ok"),
                               callback: () => {
                                   callback(true);
                               },
                               variant : "primary"
                           })
                .build()
                .show();
        });
    }

    /**
     *
     * @param message
     * @param title
     * @param placeholder
     * @param value
     * @param label
     * @param isPassword
     * @returns {Promise<any>}
     */
    prompt(
        label,
        title       = "Prompt",
        message     = null,
        placeholder = null,
        value       = null,
        isPassword  = false
    ) {
        return new Promise((resolve, reject) => {
            return this.form(
                {
                    input: {
                        label,
                        title,
                        type       : isPassword ? 'password':'text',
                        placeholder: placeholder,
                        required   : true
                    }
                },
                title,
                message ?? title,
                'prompt',
                'small'
            ).then((data) => {
                resolve(data.input);
            }).catch(reject);
        });
    }

    /**
     *
     * @param {Object} form
     * @param {String} title
     * @param {String} message
     * @param {String} name
     * @param {String} size
     * @returns {Promise<any>}
     */
    form(form, title = "Form", message = "", name = "", size = 'large') {
        let id = `passwords-form-${Math.round(Math.random() * 10)}`;

        return new Promise(async (resolve, reject) => {
            let FormTemplate  = await import(/* webpackChunkName: "Form" */ "@vue/Components/Form.vue"),
                FormComponent = Vue.extend(FormTemplate.default),
                Form          = new FormComponent({propsData: {form, title, message, id, name, size}});

            Form.$mount(UtilityService.popupContainer());
            Form.$on('submit', function(data) {
                resolve(data);
            });
            Form.$on('cancel', function() {
                reject();
            });
        });
    }

    /**
     *
     * @param title
     * @param mime
     * @param multiselect
     * @param allowDirectories
     * @returns {Promise<any>}
     */
    ncFilePicker(
        title            = "Pick a file",
        mime,
        multiselect      = false,
        allowDirectories = false
    ) {
        return new Promise((resolve) => {
            getFilePickerBuilder(title)
                .addButton({
                               label  : LocalisationService.translate("Cancel"),
                               variant: "error"
                           })
                .addButton({
                               label   : LocalisationService.translate("Ok"),
                               callback: (nodes) => {
                                   if(!multiselect && nodes[0]?.path) {
                                       resolve(nodes[0].path);
                                   } else {
                                       resolve(nodes);
                                   }
                               },
                               variant : "primary"
                           })
                .allowDirectories(allowDirectories)
                .setMimeTypeFilter(mime)
                .setMultiSelect(multiselect)
                .build()
                .pick();
        });
    }

    /**
     *
     * @param title
     * @returns {*}
     */
    ncFolderPicker(title) {
        return this.ncFilePicker(title, "httpd/unix-directory", false, true);
    }

    /**
     * Sets the value of an input field because Nextcloud does not support this
     *
     * @param value
     * @private
     */
    _setDialogValue(value) {
        let $el = document.querySelectorAll(".oc-dialog-content input");
        if($el.length === 0) {
            setTimeout(() => {
                this._setDialogValue(value);
            }, 10);
        } else {
            $el.forEach((element) => {
                element.value = value;
            });
        }
    }

    /**
     * Sets the placeholder of an input field because Nextcloud does not support this
     *
     * @param value
     * @private
     */
    _setDialogPlaceholder(value) {
        let $el = document.querySelectorAll(".oc-dialog-content input");
        if($el.length === 0) {
            setTimeout(() => {
                this._setDialogPlaceholder(value);
            }, 10);
        } else {
            $el.forEach((element) => {
                element.setAttribute("placeholder", value);
            });
        }
    }

    /**
     *
     * @param id
     * @param form
     * @private
     */
    _loadForm(id, form) {
        let $el = document.getElementById(id);
        if($el === null) {
            setTimeout(() => {
                this._loadForm(id, form);
            }, 10);
        } else {
            form.$mount(`#${id}`);

            let buttons = document.querySelectorAll(
                ".oc-dialog-buttonrow.twobuttons button"
            );
            buttons[0].innerText = LocalisationService.translate("Cancel");
            buttons[1].innerText = LocalisationService.translate("Ok");
        }
    }
})();