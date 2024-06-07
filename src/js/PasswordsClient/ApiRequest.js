/*
 * @copyright 2023 Passwords App
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the Passwords App
 * created by Marius David Wieschollek.
 */
import {ApiRequest as OriginalApiRequest} from 'passwords-client/http';

export default class ApiRequest extends OriginalApiRequest {
    /**
     *
     * @param {String} url
     * @param {Object} options
     * @returns {Promise<Response>}
     * @private
     */
    async _executeRequest(url, options) {
        try {
            let request = new Request(url, options);
            this._api.emit('request.before', request);

            return await fetch(request, options);
        } catch(e) {
            this._api.emit('request.error', e);
            throw e;
        }
    }
}