/*
 * @copyright 2023 Passwords App
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the Passwords App
 * created by Marius David Wieschollek.
 */
import LoggingService from "@js/Services/LoggingService";
import ToastService from "@js/Services/ToastService";

const handler = {
    get(target, prop, receiver) {
        const value = target[prop];
        if(value instanceof Function) {
            return function(...args) {
                LoggingService.trace(`Logger.${prop}() is deprecated and will be removed. Use LoggingService instead`, args);
                if(APP_NIGHTLY) {
                    ToastService
                        .warning([`Call to deprecated Logger.${prop}() method. Please send stack trace from browser console to Passwords app developer.`]);
                }
                return value.apply(this === receiver ? target:this, args);
            };
        }
        return value;
    }
};

let Logger = new Proxy(LoggingService, handler);


/**
 * @deprecated
 */
export default Logger;