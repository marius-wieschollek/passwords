import ClientService from "@js/Services/ClientService";

const handler = {
    get(target, prop, receiver) {
        let client = ClientService.getLegacyClient();

        const value = client[prop];
        if(value instanceof Function) {
            return function(...args) {
                return value.apply(this === receiver ? client:this, args);
            };
        }
        return value;
    }
};

let API = new Proxy({}, handler);

export default API;