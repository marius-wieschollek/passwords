/*
 * @copyright 2023 Passwords App
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the Passwords App
 * created by Marius David Wieschollek.
 */

import {EnhancedApi} from 'passwords-client/legacy';
import SettingsService from "@js/Services/SettingsService";

export default class LegacyPasswordsApi extends EnhancedApi {

    constructor(props) {
        super(props);

        this._requests = [];
        this._activeRequests = 0;
        this._queueCheckActive = false;
        this._maxRequests = 2;
        this._maxSlowRequests = 1;

        if(APP_NIGHTLY) {
            this._debug();
            window.debugPwRequestLimit = (limit = 32) => {
                this._maxRequests = limit;
                this._maxSlowRequests = limit;
                this._checkRequestQueue();
            };
        }
    }

    initialize(client, config = {}) {
        super.initialize(client, config);

        let performance = SettingsService.get('server.performance');
        if(performance === 0) this._maxRequests = 2;
        if(performance > 0 && performance < 6) this._maxRequests = performance * 3;
        if(performance === 6) this._maxRequests = 32;

        this._maxSlowRequests = this._maxRequests === 2 ? 1:2;
    }

    _sendRequest(path, data = null, method = null, dataType = 'application/json', requestOptions = {}) {
        return new Promise((resolve, reject) => {
            let priority = 2,
                name     = (Array.isArray(path) ? path[0]:path).split('.');
            if(name[0] === 'service') {
                priority = 3;
                if(['favicon', 'preview'].indexOf(name[1]) !== -1) {
                    priority = 4;
                }
            } else if(['create', 'update', 'delete', 'set'].indexOf(name[name.length - 1]) !== -1) {
                priority = 1;
            } else if(name[0] === 'session') {
                priority = 0;
            }

            this._requests.push(
                {
                    resolve,
                    reject,
                    data: {path, data, method, dataType, requestOptions},
                    priority
                }
            );

            this._checkRequestQueue();
        });
    }

    /**
     *
     * @private
     */
    _checkRequestQueue() {
        this._debug();
        if(this._queueCheckActive || this._requests.length === 0) {
            return;
        }
        this._queueCheckActive = true;
        this._requests.sort(function(a, b) {
            if(a.priority === b.priority) return 0;
            return a.priority < b.priority ? -1:1;
        });

        while(this._activeRequests < this._maxRequests && this._requests.length > 0) {
            let request = this._requests.shift();
            if(request.priority > 3 && (this._activeRequests > this._maxSlowRequests)) {
                this._requests.unshift(request);
                break;
            }

            this._activeRequests++;
            super._sendRequest(request.data.path, request.data.data, request.data.method, request.data.dataType, request.data.requestOptions)
                 .then(request.resolve)
                 .catch(request.reject)
                 .finally(() => {
                     if(this._activeRequests > 0) {
                         this._activeRequests--;
                     }
                     this._checkRequestQueue();
                 });
        }

        this._queueCheckActive = false;
    }

    async _executeRequest(url, options) {
        try {
            let request = new Request(url, options);
            this._config.events.emit('api.request.before', request);

            /**
             * This fixes the NC 28 behaviour
             * where window.fetch is overridden
             * and will ignore the options from the request
             */
            return await fetch(request, options);
        } catch(e) {
            if(e.status === 401 && this._enabled) this._enabled = false;

            this._config.events.emit('api.request.error', e);
            throw e;
        }
    }

    _debug() {
        if(APP_NIGHTLY) {
            document.body.dataset.debugLoading = this._requests.length > 0;
        }
    }
}