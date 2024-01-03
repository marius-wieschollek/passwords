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

const handler = {
    get(target, prop, receiver) {
        const value = target[prop];
        if(value instanceof Function) {
            return function(...args) {
                console.trace(`Logger.${prop}() is deprecated and will be removed. Use LoggingService instead`, args);
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