/*
 * @copyright 2022 Passwords App
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the Passwords App
 * created by Marius David Wieschollek.
 */

import Messages from "@js/Classes/Messages";
import {generateUrl} from "@nextcloud/router";

export default class RecoverHiddenItemsAction {

    /**
     *
     * @returns {Promise<void>}
     */
    async run() {
        let selection;

        try {
            selection = await this.showRecoverItemsDialog();
        } catch(e) {
            return;
        }

        let result = await this.sendRecoveryRequest(selection);
        await this.showResultDialog(result);
    }

    /**
     *
     * @returns {Promise<*>}
     */
    async showRecoverItemsDialog() {
        return Messages.form(
            {
                passwordsInvisibleInFolder: {
                    label: 'RecoverItemsInvisiblePwd',
                    type : 'checkbox',
                    title: 'RecoverItemsInvisiblePwdTitle'
                },
                invisibleInTrash: {
                    label: 'RecoverItemsInvisibleTrash',
                    type : 'checkbox',
                    title: 'RecoverItemsInvisibleTrashTitle'
                },
                passwords                 : {
                    label: 'RecoverItemsPasswords',
                    type : 'checkbox',
                    title: 'RecoverItemsPasswordsTitle'
                },
                folders                   : {
                    label: 'RecoverItemsFolders',
                    type : 'checkbox',
                    title: 'RecoverItemsFoldersTitle'
                },
                tags                      : {
                    label: 'RecoverItemsTags',
                    type : 'checkbox',
                    title: 'RecoverItemsTagsTitle'
                }
            },
            'RecoverItemsSelectTitle',
            'RecoverItemsSelectText',
            'item-recovery-form'
        );
    }

    /**
     *
     * @param selection
     * @returns {Promise<any|{success: boolean}>}
     */
    async sendRecoveryRequest(selection) {
        let url     = generateUrl(`/apps/passwords/action/recover-hidden`),
            headers = new Headers();

        headers.append('Content-Type', 'application/json');

        let options  = {method: 'POST', headers, credentials: 'include', redirect: 'error', body: JSON.stringify(selection)},
            request  = new Request(url, options),
            response = await fetch(request);

        if(response.ok && response.status === 200) {
            return await response.json();
        }

        return {success: false};
    }

    /**
     *
     * @param result
     * @returns {Promise<void>}
     */
    async showResultDialog(result) {
        if(!result.success) {
            throw new Error('RecoverItemsError');
        }

        await Messages.alert(['RecoverItemsSuccessMessage', result], 'RecoverItemsSuccessTitle');
    }
}