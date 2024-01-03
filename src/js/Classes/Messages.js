import MessageService from '@js/Services/MessageService';
import LoggingService from "@js/Services/LoggingService";
import ToastService from "@js/Services/ToastService";

const handler = {
    get(target, prop, receiver) {
        const value = target[prop];
        if(value instanceof Function) {
            return function(...args) {
                LoggingService.trace(`Messages.${prop}() is deprecated and will be removed. Use MessageService instead`, args);
                if(APP_NIGHTLY) {
                    ToastService
                        .warning([`Call to deprecated Messages.${prop}() method. Please send stack trace from browser console to Passwords app developer.`]);
                }
                return value.apply(this === receiver ? target:this, args);
            };
        }
        return value;
    }
};

let Messages = new Proxy(MessageService, handler);

/**
 * @deprecated
 */
export default Messages;