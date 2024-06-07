/*
 * @copyright 2023 Passwords App
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the Passwords App
 * created by Marius David Wieschollek.
 */

import EnhancedClassLoader from 'passwords-client/enhanced-class-loader';
import PasswordsClient from "passwords-client";
import LegacyPasswordsApi from "@js/PasswordsClient/LegacyPasswordsApi";
import SettingsService from "@js/Services/SettingsService";
import ApiRequest from "@js/PasswordsClient/ApiRequest";

export default new class ClientService {

    constructor() {
        this._events = null;
        this._client = null;
    }

    initialize(baseUrl, user, token, events = null) {
        let server = {baseUrl, user, token};
        let config = {baseUrl, user, token};

        let classes = {
            'legacy'         : () => {
                let client = new LegacyPasswordsApi();
                client.initialize(this.getClient(), this._getLegacyConfig());
                return client;
            },
            'network.request': ApiRequest
        };

        let classLoader = new EnhancedClassLoader(classes);

        this._client = new PasswordsClient(server, config, classLoader);
        this._events = events;
    }

    /**
     * @return {PasswordsClient}
     */
    getClient() {
        return this._client;
    }

    /**
     * @return {LegacyPasswordsApi}
     */
    getLegacyClient() {
        return this.getClient().getInstance('legacy');
    }

    _getLegacyConfig() {
        let
            cseMode    = SettingsService.get('user.encryption.cse') === 1 ? 'CSEv1r1':'none',
            folderIcon = SettingsService.get('server.theme.folder.icon'),
            hashLength = SettingsService.get('user.password.security.hash');

        let config = {folderIcon, hashLength, cseMode};
        if(this._events !== null) {
            config.events = this._events;
        }

        SettingsService.observe('user.password.security.hash', (setting) => {
            this.getLegacyClient().config.hashLength = setting.value;
        });

        return config;
    }
};