/*
 * @copyright 2023 Passwords App
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the Passwords App
 * created by Marius David Wieschollek.
 */

import {loadState} from "@nextcloud/initial-state";
import Logger from "@js/Classes/Logger";

export default new class DatabaseService {

    get DATABASE_NAME() {
        return 'nc_passwords.' + loadState('passwords', 'api-user', null);
    }

    get SETTINGS_TABLE() {
        return 'settings';
    }

    constructor() {
        this._db = null;
    }

    /**
     * @public
     * @return {Promise<void>}
     */
    async init() {
        if(!window.indexedDB) return;

        return new Promise((resolve, reject) => {
            let request = window.indexedDB.open(this.DATABASE_NAME, 1);

            request.onerror = (event) => {
                Logger.error(new Error('Could not open database'), {event});
                resolve();
            };

            request.onupgradeneeded = (event) => {
                let db = event.target.result;

                db.createObjectStore(this.SETTINGS_TABLE, {keyPath: 'key'});
            };

            request.onsuccess = (event) => {
                this._db = event.target.result;
                resolve();
            };
        });
    }

    /**
     * @public
     * @param {String} table
     * @param {(String|Number)} key
     * @return {Promise<(Object|String|Number|null)>}
     */
    async get(table, key) {
        if(this._db === null) await this.init();

        if(await this._hasEntry(table, key)) {
            return await this._readEntry(table, key);
        }

        return null;
    }

    /**
     * @public
     * @param {String} table
     * @return {Promise<(Object|String|Number|null)>}
     */
    async getAll(table) {
        if(this._db === null) await this.init();

        return await this._readAll(table);
    }

    /**
     *
     * @param {String} table
     * @param {(String|Number)} key
     * @param value
     * @returns {Promise<void>}
     */
    async set(table, key, value) {
        if(this._db === null) await this.init();

        return await this._writeEntry(table, key, value);
    }

    /**
     *
     * @param {String} table
     * @param {(String|Number)} key
     * @returns {Promise<Boolean>}
     */
    async has(table, key) {
        if(this._db === null) await this.init();

        return await this._hasEntry(table, key);
    }

    /**
     *
     * @param {String} table
     * @param {(String|Number)} key
     * @returns {Promise<void>}
     */
    async remove(table, key) {
        if(this._db === null) await this.init();

        return await this._deleteEntry(table, key);
    }


    /**
     * @param {String} table
     * @param {String} key
     * @return {Promise<Boolean>}
     * @private
     */
    async _hasEntry(table, key) {
        return new Promise((resolve, reject) => {
            let transaction = this._db.transaction([table], 'readonly'),
                count       = transaction.objectStore(table).count(IDBKeyRange.only(key));

            count.onsuccess = (e) => {
                resolve(e.target.result !== 0);
            };

            count.onerror = reject;
        });
    }

    /**
     * @param {String} table
     * @param {String} key
     * @return {Promise<Number>}
     * @private
     */
    async _readEntry(table, key) {
        return new Promise((resolve, reject) => {
            let transaction = this._db.transaction([table], 'readonly'),
                read        = transaction.objectStore(table).get(key);

            read.onsuccess = (e) => {
                resolve(JSON.parse(e.target.result.value));
            };

            read.onerror = reject;
        });
    }

    /**
     * @param {String} table
     * @return {Promise<Number>}
     * @private
     */
    async _readAll(table) {
        return new Promise((resolve, reject) => {
            let transaction = this._db.transaction([table], 'readonly'),
                read        = transaction.objectStore(table).getAll();

            read.onsuccess = (e) => {
                let data = {};
                for(let entry of e.target.result) {
                    data[entry.key] = JSON.parse(entry.value);
                }

                resolve(data);
            };

            read.onerror = reject;
        });
    }

    /**
     * @param {String} table
     * @param {String} key
     * @param {*} value
     * @return {Promise<void>}
     * @private
     */
    async _writeEntry(table, key, value) {
        return new Promise((resolve, reject) => {
            let transaction = this._db.transaction([table], 'readwrite'),
                write       = transaction.objectStore(table).put({key, value: JSON.stringify(value)});

            write.onsuccess = resolve;
            write.onerror = reject;
        });
    }

    /**
     * @param {String} table
     * @param {String} key
     * @return {Promise<void>}
     * @private
     */
    async _deleteEntry(table, key) {
        return new Promise((resolve, reject) => {
            let transaction = this._db.transaction([table], 'readwrite'),
                write       = transaction.objectStore(table).delete(key);

            write.onsuccess = resolve;
            write.onerror = reject;
        });
    }
};