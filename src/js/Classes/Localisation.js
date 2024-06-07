/*
 * @copyright 2023 Passwords App
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the Passwords App
 * created by Marius David Wieschollek.
 */
import LocalisationService from '@js/Services/LocalisationService';
import LoggingService from "@js/Services/LoggingService";
import ToastService from "@js/Services/ToastService";

const handler = {
    get(target, prop, receiver) {
        const value = target[prop];
        if(value instanceof Function) {
            return function(...args) {
                LoggingService.trace(`Localisation.${prop}() is deprecated and will be removed. Use LocalisationService instead`, args);
                if(APP_NIGHTLY) {
                    ToastService
                        .warning([`Call to deprecated Localisation.${prop}() method. Please send stack trace from browser console to Passwords app developer.`]);
                }
                return value.apply(this === receiver ? target:this, args);
            };
        }
        return value;
    }
};

let Localisation = new Proxy(LocalisationService, handler);

/**
 * @deprecated
 */
export default Localisation;