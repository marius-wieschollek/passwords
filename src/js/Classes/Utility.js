/*
 * @copyright 2023 Passwords App
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the Passwords App
 * created by Marius David Wieschollek.
 */
import UtilityService from '@js/Services/UtilityService';
import LoggingService from "@js/Services/LoggingService";
import ToastService from "@js/Services/ToastService";

const handler = {
    get(target, prop, receiver) {
        const value = target[prop];
        if(value instanceof Function) {
            return function(...args) {
                LoggingService.trace(`Utility.${prop}() is deprecated and will be removed. Use UtilityService instead`, args);
                if(APP_NIGHTLY) {
                    ToastService
                        .warning([`Call to deprecated Utility.${prop}() method. Please send stack trace from browser console to Passwords app developer.`]);
                }
                return value.apply(this === receiver ? target:this, args);
            };
        }
        return value;
    }
};

let Utility = new Proxy(UtilityService, handler);

/**
 * @deprecated
 */
export default Utility;