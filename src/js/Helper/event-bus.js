/*
 * @copyright 2026 Passwords App
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the Passwords App
 * created by Marius David Wieschollek.
 */

import {emit, subscribe, unsubscribe} from "@nextcloud/event-bus";

const emitMultiple = function(events, data) {
    for(let event of events) {
        emit(event, data);
    }
}

const subscribeOnce = function(event, callback) {
    let func = (data) => {
        callback(data);
        unsubscribe(event, func);
    }

    subscribe(event, func);
}

export {
    emit,
    subscribe,
    unsubscribe,
    emitMultiple,
    subscribeOnce,
}