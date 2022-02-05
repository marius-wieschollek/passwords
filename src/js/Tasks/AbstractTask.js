/*
 * @copyright 2022 Passwords App
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the Passwords App
 * created by Marius David Wieschollek.
 */

import EventEmitter from "eventemitter3";

export default class AbstractTask {

    get events() {

    }

    constructor() {
        this._events = new EventEmitter();
    }

}