/*
 * @copyright 2023 Passwords App
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the Passwords App
 * created by Marius David Wieschollek.
 */
import SettingsService from "@js/Services/SettingsService";
import MessageService from "@js/Services/MessageService";

export default class UserSettingsResetAction {

    async run() {
        let confirmed = await MessageService.confirm(
            'This will reset all settings to their defaults. Do you want to continue?',
            'Reset all settings',
            true
        );

        if(confirmed) {
            await this._resetSettings();
        }

        return confirmed;
    }

    async _resetSettings() {
        let settings = SettingsService.getAll(),
            promises = [];
        for(let i in settings) {
            if(settings.hasOwnProperty(i)) promises.push(SettingsService.reset(i));
        }

        await Promise.all(promises);
    }
}