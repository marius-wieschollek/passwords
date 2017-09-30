class Events {

    /**
     *
     */
    constructor() {
        this.events = [];
    }

    /**
     *
     * @param event
     * @param callback
     */
    on(event, callback) {
        if (!this.events.hasOwnProperty(event)) {
            this.events[event] = [];
        }

        this.events[event].push(callback);
    }

    /**
     *
     * @param event
     * @param callback
     */
    off(event, callback) {
        if (!this.events.hasOwnProperty(event)) return;
        let callbacks = this.events[event];

        while (callbacks.indexOf(callback) !== -1) {
            let index = callbacks.indexOf(callback);
            callbacks = callbacks.remove(index);
        }
    }

    /**
     *
     * @param event
     * @param data
     */
    run(event, data = {}) {
        if (!this.events.hasOwnProperty(event)) return;
        let callbacks = this.events[event];
        data = {event: event, data: data};

        for (let i = 0; i < callbacks.length; i++) {
            try {
                callbacks[i](data);
            } catch (e) {
                console.error(e);
            }
        }
    }
}

const PwEvents = new Events();

export default PwEvents
