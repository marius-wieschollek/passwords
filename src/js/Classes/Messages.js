import MessageService from '@js/Services/MessageService';

const handler = {
    get(target, prop, receiver) {
        const value = target[prop];
        if(value instanceof Function) {
            return function(...args) {
                console.trace(`Messages.${prop}() is deprecated and will be removed. Use MessageService instead`, args);
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